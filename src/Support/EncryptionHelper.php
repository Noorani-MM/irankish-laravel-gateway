<?php

namespace IranKish\Support;

use RuntimeException;

/**
 * این کلاس وظیفه ساخت پاکت دیجیتال (Digital Envelope) را برای ارتباط امن با درگاه ایران‌کیش دارد.
 * ساختار تابع طبق مستند فنی نسخه 9 انجام شده است.
 *
 * - Base String بسته به نوع تراکنش ساخته می‌شود.
 * - سپس با AES-128-CBC رمزگذاری می‌شود.
 * - هش SHA256 از متن رمز شده گرفته می‌شود.
 * - کلید AES و هش با RSA رمزگذاری می‌شوند.
 * - خروجی نهایی شامل data و iv در قالب hex است.
 *
 * Padding RSA از تنظیمات پکیج (config('irankish.rsa_padding')) خوانده می‌شود.
 */
final class EncryptionHelper
{
    public const RSA_PADDING_PKCS1 = OPENSSL_PKCS1_PADDING;
    public const RSA_PADDING_OAEP  = OPENSSL_PKCS1_OAEP_PADDING;

    /**
     * تولید پاکت دیجیتال جهت ارسال در authenticationEnvelope
     */
    public static function makeAuthenticationEnvelope(
        string $terminalId,
        string $passPhrase,
        int|string $amount,
        string $publicKeyPem,
        bool $isMultiplex = false,
        array $splits = []
    ): array {
        self::validateInputs($terminalId, $passPhrase, $amount, $publicKeyPem, $isMultiplex, $splits);

        // نوع Padding از config خوانده می‌شود، پیش‌فرض PKCS1
        $rsaPadding = config('irankish.rsa_padding', self::RSA_PADDING_PKCS1);

        // ساخت رشته پایه (Base String)
        $baseString = self::buildBaseString($terminalId, $passPhrase, (string)$amount, $isMultiplex, $splits);

        // تولید کلید و IV تصادفی
        $aesKey = random_bytes(16);
        $iv     = random_bytes(16);

        // رمزگذاری Base String با AES
        $cipher = self::aesEncrypt($baseString, $aesKey, $iv);

        // محاسبه هش SHA256 از متن رمز شده
        $hash = hash('sha256', $cipher, true);

        // ترکیب کلید AES و هش برای رمزگذاری RSA
        $blob = $aesKey . $hash;

        // رمزگذاری نهایی با RSA
        $rsa = self::rsaEncrypt($blob, $publicKeyPem, $rsaPadding);

        return [
            'data' => strtoupper(bin2hex($rsa)),
            'iv'   => strtoupper(bin2hex($iv)),
        ];
    }

    /**
     * ساخت Base String
     * تراکنش عادی: amount(12) + passPhrase + terminalId + "00"
     * تسهیمی: amount(12) + passPhrase + terminalId + "01" + (iban + amount)
     */
    private static function buildBaseString(
        string $terminalId,
        string $passPhrase,
        string $amount,
        bool $isMultiplex,
        array $splits
    ): string {
        $amount12 = self::pad12($amount);
        $base = $amount12 . $passPhrase . $terminalId . ($isMultiplex ? '01' : '00');

        if ($isMultiplex) {
            foreach ($splits as $split) {
                $iban  = self::normalizeIban($split['iban']);
                $amt12 = self::pad12($split['amount']);
                $base .= $iban . $amt12;
            }
        }

        return $base;
    }

    /**
     * رمزگذاری AES-128-CBC
     */
    private static function aesEncrypt(string $plain, string $key, string $iv): string
    {
        $cipher = openssl_encrypt($plain, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
        if ($cipher === false) {
            throw new RuntimeException('AES encryption failed.');
        }
        return $cipher;
    }

    /**
     * رمزگذاری RSA با کلید عمومی
     */
    private static function rsaEncrypt(string $data, string $publicKeyPem, int $padding): string
    {
        $pub = openssl_pkey_get_public($publicKeyPem);
        if ($pub === false) {
            throw new RuntimeException('Invalid public key.');
        }

        $encrypted = '';
        $ok = openssl_public_encrypt($data, $encrypted, $pub, $padding);
        openssl_free_key($pub);

        if (!$ok) {
            throw new RuntimeException('RSA encryption failed.');
        }

        return $encrypted;
    }

    /**
     * نرمال‌سازی شماره شبا (IBAN)
     * در حالت تسهیمی، "IR" باید به "2718" تبدیل شود.
     */
    private static function normalizeIban(string $iban): string
    {
        $iban = strtoupper(str_replace(' ', '', $iban));
        return str_starts_with($iban, 'IR')
            ? '2718' . substr($iban, 2)
            : $iban;
    }

    /**
     * صفرگذاری عدد تا ۱۲ رقم
     */
    private static function pad12(int|string $value): string
    {
        $digits = preg_replace('/\D+/', '', (string)$value) ?: '0';
        return str_pad($digits, 12, '0', STR_PAD_LEFT);
    }

    /**
     * بررسی ورودی‌ها
     */
    private static function validateInputs(
        string $terminalId,
        string $passPhrase,
        int|string $amount,
        string $publicKeyPem,
        bool $isMultiplex,
        array $splits
    ): void {
        if (!preg_match('/^\d{8}$/', $terminalId)) {
            throw new RuntimeException('terminalId must be exactly 8 digits.');
        }

        if (strlen($passPhrase) !== 16) {
            throw new RuntimeException('passPhrase must be 16 characters.');
        }

        if ((int)$amount <= 0) {
            throw new RuntimeException('amount must be greater than zero.');
        }

        if (!str_contains($publicKeyPem, 'BEGIN PUBLIC KEY')) {
            throw new RuntimeException('Invalid public key format.');
        }

        if ($isMultiplex && empty($splits)) {
            throw new RuntimeException('Multiplex mode requires splits.');
        }
    }
}
