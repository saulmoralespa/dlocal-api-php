<?php

use Saulmoralespa\Dlocal\Client;
use PHPUnit\Framework\TestCase;

class DLocalTest extends TestCase
{
    public $dLocal;

    protected function setUp()
    {
        $dotenv = Dotenv\Dotenv::createMutable(__DIR__ . '/../');
        $dotenv->load();

        $xLogin = $_ENV['X_Login'];
        $xTranskey = $_ENV['X_Trans_Key'];
        $secretKey = $_ENV['secret_key'];
        $this->dLocal = new Client($xLogin, $xTranskey, $secretKey);
        $this->dLocal->sandboxMode(true);
    }

    public function testPayments()
    {
        $params = array(
            'amount' => 120.0,
            'currency' => 'USD',
            'country' => 'PA',
            'payment_method_flow' => 'REDIRECT',
            'payer' =>
                array(
                    'name' => 'Thiago Gabriel',
                    'email' => 'thiago@example.com',
                    'document' => '53033315550',
                    'user_reference' => '12345',
                    'address' =>
                        array(
                            'state' => 'Rio de Janeiro',
                            'city' => 'Volta Redonda',
                            'zip_code' => '27275-595',
                            'street' => 'Servidao B-1',
                            'number' => '1106',
                        ),
                ),
            'order_id' => time(),
            'description' => 'Testing Sandbox',
            'notification_url' => 'http://merchant.com/notifications',
            'callback_url' => 'http://merchant.com/callback'
        );

        $response = $this->dLocal->payments($params);
        var_dump($response);
        $this->assertObjectHasAttribute('redirect_url', $response);
    }

    public function testPaymentStatus()
    {
        $paymentId = "PAY4334346343";
        $response = $this->dLocal->paymentStatus($paymentId);
        $this->assertObjectHasAttribute('status_code', $response);
    }
}