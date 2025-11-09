<?php

namespace IranKish\Tests;

use IranKish\IranKishGateway;
use IranKish\Enums\TransactionType;
use IranKish\Exceptions\IranKishException;
use Illuminate\Support\Facades\Http;

class GatewayTest extends TestCase
{
    protected IranKishGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = new IranKishGateway(config('irankish'));
    }

    /** @test */
    public function it_can_request_a_token_successfully()
    {
        Http::fake([
            'https://ikc.shaparak.ir/api/v3/tokenization/make' => Http::response([
                'responseCode' => '00',
                'description'  => 'Success',
                'result'       => ['token' => 'FAKETOKEN123'],
            ], 200),
        ]);

        $response = $this->gateway->requestToken(10000, TransactionType::PURCHASE, [
            'paymentId' => 'ORDER-01',
        ]);

        $this->assertEquals('FAKETOKEN123', $response['token']);
        $this->assertEquals('Purchase', $response['transactionType']);
        $this->assertArrayHasKey('raw', $response);
    }

    /** @test */
    public function it_throws_exception_if_tokenization_fails()
    {
        Http::fake([
            'https://ikc.shaparak.ir/api/v3/tokenization/make' => Http::response([
                'responseCode' => '12',
                'description'  => 'Invalid Terminal ID',
            ], 200),
        ]);

        $this->expectException(IranKishException::class);
        $this->gateway->requestToken(10000);
    }

    /** @test */
    public function redirect_data_returns_correct_structure()
    {
        $token = 'FAKETOKEN123';
        $data = $this->gateway->redirectData($token);

        $this->assertEquals('https://ikc.shaparak.ir/iuiv3/IPG/Index/', $data['transactionUrl']);
        $this->assertEquals('POST', $data['method']);
        $this->assertEquals($token, $data['fields']['tokenIdentity']);
    }

    /** @test */
    public function it_can_confirm_a_transaction_successfully()
    {
        Http::fake([
            'https://ikc.shaparak.ir/api/v3/confirmation/purchase' => Http::response([
                'responseCode' => '00',
                'description'  => 'Payment verified',
                'result'       => ['amount' => 10000],
            ], 200),
        ]);

        $data = $this->gateway->confirm('FAKETOKEN123', '123456789012', '123456');
        $this->assertEquals('00', $data['responseCode']);
    }

    /** @test */
    public function confirm_throws_exception_on_failure()
    {
        Http::fake([
            'https://ikc.shaparak.ir/api/v3/confirmation/purchase' => Http::response([
                'responseCode' => '14',
                'description'  => 'Invalid token',
            ], 200),
        ]);

        $this->expectException(IranKishException::class);
        $this->gateway->confirm('BADTOKEN', '123456789012', '123456');
    }

    /** @test */
    public function reverse_returns_json_response()
    {
        Http::fake([
            'https://ikc.shaparak.ir/api/v3/confirmation/reversePurchase' => Http::response([
                'responseCode' => '00',
                'description'  => 'Reversed successfully',
            ], 200),
        ]);

        $resp = $this->gateway->reverse('FAKETOKEN123', '123456789012', '654321');
        $this->assertEquals('00', $resp['responseCode']);
    }

    /** @test */
    public function inquiry_returns_json_response()
    {
        Http::fake([
            'https://ikc.shaparak.ir/api/v3/inquiry/single' => Http::response([
                'responseCode' => '00',
                'description'  => 'Found',
                'result'       => ['amount' => 10000],
            ], 200),
        ]);

        $resp = $this->gateway->inquiry([
            'findOption' => 2,
            'tokenIdentity' => 'FAKETOKEN123',
        ]);

        $this->assertEquals('00', $resp['responseCode']);
    }
}
