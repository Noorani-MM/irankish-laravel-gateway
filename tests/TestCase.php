<?php

namespace IranKish\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use IranKish\IranKishServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            IranKishServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('irankish', [
            'base_url'     => 'https://ikc.shaparak.ir',
            'terminal_id'  => '12345678',
            'acceptor_id'  => '87654321',
            'pass_phrase'  => '1234567890123456',
            'callback_url' => 'https://example.com/callback',
            'public_key'   => file_get_contents(__DIR__ . '/stub_public.pem'),
            'rsa_padding'  => OPENSSL_PKCS1_PADDING,
        ]);
    }
}
