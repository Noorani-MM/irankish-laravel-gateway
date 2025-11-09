<?php

namespace IranKish\Exceptions;

use Exception;

/**
 * Generic IranKish gateway exception.
 * Wraps logical and transport-level errors to present a clean API.
 */
class IranKishException extends Exception
{
    public static function fromGateway(string $message, ?string $code = null): self
    {
        $ex = new self($message);
        if ($code !== null) {
            $ex->code = $code;
        }
        return $ex;
    }
}
