<?php

namespace IranKish\Tests;

use IranKish\Support\IkcResponse;

class IkcResponseTest extends TestCase
{
    /** @test */
    public function it_detects_successful_response()
    {
        $response = new IkcResponse([
            'responseCode' => '00',
            'description' => 'Success',
            'token' => 'XYZ123',
            'retrievalReferenceNumber' => '123456789012',
            'systemTraceAuditNumber' => '654321',
            'amount' => 50000,
        ]);

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('XYZ123', $response->token());
        $this->assertEquals(50000, $response->amount());
        $this->assertEquals('123456789012', $response->rrn());
        $this->assertEquals('654321', $response->stan());
    }

    /** @test */
    public function it_returns_json_string_on_cast()
    {
        $response = new IkcResponse(['responseCode' => '12']);
        $json = (string) $response;

        $this->assertStringContainsString('responseCode', $json);
    }
}
