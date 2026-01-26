<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Models\ZakatCalculation calculateZakat(\App\Models\Family $family, int $hijriYear, array $data)
 * @method static float getNisabAmount(string $type = 'silver', string $currency = 'PKR')
 * @method static \App\Models\ZakatPayment recordPayment(\App\Models\ZakatCalculation $calculation, array $data)
 * @method static \App\Models\ZakatCalculation autoCalculateFromAccounts(\App\Models\Family $family, int $hijriYear)
 * @method static void sendZakatReminders()
 * @method static array getZakatHistory(\App\Models\Family $family)
 * @method static int getCurrentHijriYear()
 *
 * @see \App\Services\ZakatService
 */
class ZakatService extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\ZakatService::class;
    }
}