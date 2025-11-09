<?php

namespace IranKish\Tests;

use IranKish\Support\EncryptionHelper;

class EncryptionHelperTest extends TestCase
{
    /** @test */
    public function it_creates_valid_authentication_envelope()
    {
        $config = config('irankish');

        $result = EncryptionHelper::makeAuthenticationEnvelope(
            terminalId: $config['terminal_id'],
            passPhrase: $config['pass_phrase'],
            amount: 10000,
            publicKeyPem: $config['public_key'],
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('iv', $result);
        $this->assertTrue(strlen($result['data']) > 100, 'RSA data too short');
        $this->assertTrue(strlen($result['iv']) === 32, 'IV length mismatch');
    }

    /** @test */
    public function it_fails_with_invalid_terminal_id()
    {
        $this->expectException(\RuntimeException::class);
        EncryptionHelper::makeAuthenticationEnvelope(
            terminalId: '12AB5678',
            passPhrase: '1234567890123456',
            amount: 10000,
            publicKeyPem: config('irankish.public_key')
        );
    }
}
