<?php

namespace IranKish\Tests;

use IranKish\IranKishServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
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
            'terminal_id' => '12345678',
            'acceptor_id' => '87654321',
            'password'    => 'TESTPASS',
            'public_key'  => file_get_contents(__DIR__.'/pubkey.pem'),
            'revert_url'  => 'https://example.com/callback',
        ]);
    }
}
