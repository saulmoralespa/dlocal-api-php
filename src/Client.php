<?php


namespace Saulmoralespa\Dlocal;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Utils;


class Client
{
    const API_BASE_PAYMENT_URL = "https://api.dlocal.com/";
    const SANDBOX_API_BASE_PAYMENT_URL = "https://sandbox.dlocal.com/";

    const API_BASE_CASH_PAYMENT_URL = "https://api.dlocal.com/api_curl/cashout_api/";
    const SANDBOX_API_BASE_CASH_PAYMENT_URL = "https://sandbox.dlocal.com/api_curl/cashout_api/";

    protected static $_sandbox = false;
    private $xLogin;
    private $xTransKey;
    private $secretKey;

    public function __construct($xLogin, $xTransKey, $secretKey)
    {
        $this->xLogin = $xLogin;
        $this->xTransKey = $xTransKey;
        $this->secretKey = $secretKey;
    }

    public function sandboxMode(bool $status = false)
    {
        if ($status) self::$_sandbox = true;
    }

    public static function getBasePaymentUrl()
    {
        if(self::$_sandbox)
            return self::SANDBOX_API_BASE_PAYMENT_URL;
        return self::API_BASE_PAYMENT_URL;
    }

    public static function getBaseCashPaymentUrl()
    {
        if(self::$_sandbox)
            return self::SANDBOX_API_BASE_CASH_PAYMENT_URL;
        return self::API_BASE_CASH_PAYMENT_URL;
    }

    public function cliente($cash = false)
    {
        return new GuzzleClient([
            'base_uri' => $cash ? self::getBaseCashPaymentUrl() : self::getBasePaymentUrl()
        ]);
    }

    public function payments(array $params)
    {
        try {

            $xDate = date('Y-m-d\TH:i:s.u\Z');

            $response = $this->cliente()->post("payments",
                [
                    "headers" => [
                        "X-Date" => $xDate,
                        "X-Login" => $this->xLogin,
                        "X-Trans-Key" => $this->xTransKey,
                        "Content-Type" => "application/json",
                        "Authorization" => "V2-HMAC-SHA256, Signature: " . self::generateSignature($xDate, $params)
                    ],
                    "json" => $params
                ]);
            return self::responseJson($response);
        }catch(RequestException $exception){
            $message = self::getErrorMessage($exception->getMessage());
            throw new \Exception($message);
        }
    }

    public function paymentStatus($paymentId)
    {
        try{

            $xDate = date('Y-m-d\TH:i:s.u\Z');

            $response = $this->cliente()->get("payments/$paymentId/status", [
                "headers" => [
                    "X-Date" => $xDate,
                    "X-Login" => $this->xLogin,
                    "X-Trans-Key" => $this->xTransKey,
                    "Authorization" => "V2-HMAC-SHA256, Signature: " . self::generateSignature($xDate)
                ]
            ]);
            return self::responseJson($response);
        }catch(RequestException $exception){
            $message = self::getErrorMessage($exception->getMessage());
            throw new \Exception($message);
        }
    }

    protected function generateSignature($xDate, array $body = [])
    {
        $data = "$this->xLogin$xDate";
        $data .= empty($body) ? '' : Utils::jsonEncode($body);
        return hash_hmac("sha256", $data, $this->secretKey);
    }

    public static function responseJson($response)
    {
        return Utils::jsonDecode(
            $response->getBody()->getContents()
        );
    }

    public static function getErrorMessage($response)
    {
        $pattern = "~{(.*?)}~";

        preg_match($pattern, $response, $matches);

        if (empty($matches))
            return $response;

        $json = Utils::jsonDecode($matches[0]);

        return $json->message;

    }
}