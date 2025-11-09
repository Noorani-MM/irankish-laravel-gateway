<?php

namespace IranKish\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \IranKish\IranKish
 *
 * Facade رسمی پکیج درگاه پرداخت ایران‌کیش.
 * این کلاس میانبری است برای دسترسی به IranKish بدون نیاز به ساخت نمونه‌ی مستقیم.
 *
 * @method static \IranKish\Support\IkcResponse makeToken(int $amount, string|null $paymentId = null)
 *         ارسال درخواست ساخت توکن پرداخت.
 *         - $amount: مبلغ تراکنش به ریال.
 *         - $paymentId: شناسه‌ی دلخواه سفارش (اختیاری).
 *         پاسخ شامل token و کد وضعیت است.
 *
 * @method static \IranKish\Support\IkcResponse confirm(string $tokenIdentity, string $rrn, string $stan)
 *         تأیید نهایی تراکنش پس از بازگشت کاربر از درگاه.
 *         - $tokenIdentity: همان توکنی که از درگاه برگشته.
 *         - $rrn: شماره ارجاع بانکی.
 *         - $stan: شماره پیگیری تراکنش.
 *
 * @method static \IranKish\Support\IkcResponse reverse(string $tokenIdentity, string $rrn, string $stan)
 *         بازگشت مبلغ (Reverse) برای تراکنش‌های ناموفق یا نیازمند استرداد.
 *
 * @method static \IranKish\Support\IkcResponse inquiry(string $rrn)
 *         استعلام وضعیت تراکنش بر اساس شماره ارجاع بانکی.
 *
 * نمونه استفاده:
 * ```php
 * use IranKish\Facades\IranKish;
 *
 * // ۱. ایجاد توکن پرداخت
 * $tokenResponse = IranKish::makeToken(100000, 'ORDER-1234');
 * if ($tokenResponse->isSuccessful()) {
 *     $token = $tokenResponse->token();
 * }
 *
 * // ۲. تأیید پس از بازگشت از درگاه
 * $confirmResponse = IranKish::confirm($token, $rrn, $stan);
 * ```
 */
class IranKish extends Facade
{
    /**
     * نام کلید ثبت‌شده در Service Container
     */
    protected static function getFacadeAccessor(): string
    {
        return 'irankish';
    }
}
