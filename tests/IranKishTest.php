<?php

namespace IranKish\Tests;

use IranKish\Facades\IranKish;
use IranKish\Support\IkcResponse;

class IranKishTest extends TestCase
{
    /** @test */
    public function facade_is_accessible()
    {
        $this->assertTrue(class_exists(IranKish::class));
        $this->assertInstanceOf(\IranKish\IranKish::class, app('irankish'));
    }

    /** @test */
    public function make_token_returns_ikcresponse_mock()
    {
        // mock HTTP call by overriding sendRequest
        $mock = $this->getMockBuilder(\IranKish\IranKish::class)
            ->onlyMethods(['sendRequest'])
            ->getMock();

        $mock->method('sendRequest')
            ->willReturn([
                'responseCode' => '00',
                'description' => 'Mock Success',
                'token' => 'TEST_TOKEN',
            ]);

        $response = $mock->makeToken(50000);

        $this->assertInstanceOf(IkcResponse::class, $response);
        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('TEST_TOKEN', $response->token());
    }
}
