<?php

namespace IranKish\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * IranKish Facade.
 *
 * @method static array requestToken(int $amount, ?\IranKish\Enums\TransactionType $type = null, array $options = [])
 * @method static array requestSpecialToken(int $amount, ?\IranKish\Enums\TransactionType $type = null, array $options = [])
 * @method static void redirect(string $token)
 * @method static array redirectData(string $token)
 * @method static array confirm(string $token, string $rrn, string $stan)
 * @method static array reverse(string $token, string $rrn, string $stan)
 * @method static array inquiry(array $criteria)
 */
class IranKish extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'irankish.gateway';
    }
}
