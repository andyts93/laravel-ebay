<?php

namespace Andyts93\LaravelEbay\Facades;

use Andyts93\LaravelEbay\Models\EbaySetting;
use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed get(string $key, mixed $default = null)
 * @method static EbaySetting set(string $key, mixed $value, string $type = 'string')
 * @method static array getPublic()
 * @method static void clearCache()
 */
class EbaySettings extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return EbaySetting::class;
    }
}
