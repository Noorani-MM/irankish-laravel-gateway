<?php

namespace IranKish\Support;

/**
 * Value object mirroring IKC response body.
 *
 * Typical shapes:
 *  - Tokenization (make): { responseCode: "00", description: "...", result: { token: "...", responseCode: "00" } }
 *  - Confirmation (purchase): { responseCode: "00", description: "...", ...other fields }
 */
class IkcResponse
{
    /** @var string|null */
    public $responseCode;

    /** @var string|null */
    public $description;

    /** @var array|null */
    public $result;

    /** @var array Raw response */
    public $raw;

    public function __construct(array $raw)
    {
        $this->raw          = $raw;
        $this->responseCode = $raw['responseCode'] ?? null;
        $this->description  = $raw['description']  ?? null;
        $this->result       = $raw['result']       ?? null;
    }

    public function ok(): bool
    {
        return $this->responseCode === '00';
    }
}
