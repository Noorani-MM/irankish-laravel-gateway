<?php

namespace IranKish\Http;

use Illuminate\Support\Facades\Http;

/**
 * Lightweight HTTP client wrapper around Laravel HTTP client.
 * Handles JSON requests, base URL, and sensible defaults.
 */
class Client
{
    protected string $baseUrl = 'https://ikc.shaparak.ir/api/v3';

    public function __construct(
        protected array $config = []
    ) {
        // Allow base URL override for testing/sandboxing (if needed)
        if (!empty($config['base_url'])) {
            $this->baseUrl = rtrim($config['base_url'], '/');
        }
    }

    /**
     * POST JSON to a path under the API base URL.
     * @param string $path e.g. 'tokenization/make'
     * @param array $payload
     * @return \Illuminate\Http\Client\Response
     */
    public function post(string $path, array $payload)
    {
        return Http::withHeaders([
            'Accept' => 'application/json',
        ])
            ->timeout(30)
            ->asJson()
            ->post($this->baseUrl . '/' . ltrim($path, '/'), $payload);
    }
}
