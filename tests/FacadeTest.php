<?php

namespace IranKish\Tests;

use IranKish\Facades\IranKish;
use IranKish\Enums\TransactionType;
use Illuminate\Support\Facades\Http;

class FacadeTest extends TestCase
{
    /** @test */
    public function facade_provides_token_request_method()
    {
        Http::fake([
            'https://ikc.shaparak.ir/api/v3/tokenization/make' => Http::response([
                'responseCode' => '00',
                'description'  => 'OK',
                'result'       => ['token' => 'FACAD123'],
            ], 200),
        ]);

        $response = IranKish::requestToken(5000, TransactionType::PURCHASE);
        $this->assertEquals('FACAD123', $response['token']);
    }
}
