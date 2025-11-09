<?php

namespace IranKish;

use Exception;
use IranKish\Support\EncryptionHelper;
use IranKish\Support\IkcResponse;

/**
 * این کلاس نقطه‌ی اصلی ارتباط با وب‌سرویس ایران‌کیش است.
 * درخواست‌ها را به درگاه ارسال و پاسخ‌ها را در قالب IkcResponse برمی‌گرداند.
 *
 * ساختار هر متد با استانداردهای نسخه ۹ مستند ایران‌کیش هماهنگ است.
 */
class IranKish
{
    protected string $baseUrl;
    protected string $terminalId;
    protected string $acceptorId;
    protected string $passPhrase;
    protected string $publicKey;
    protected string $callbackUrl;

    public function __construct()
    {
        $this->baseUrl     = rtrim(config('irankish.base_url', 'https://ikc.shaparak.ir'), '/');
        $this->terminalId  = config('irankish.terminal_id');
        $this->acceptorId  = config('irankish.acceptor_id');
        $this->passPhrase  = config('irankish.pass_phrase');
        $this->publicKey   = config('irankish.public_key');
        $this->callbackUrl = config('irankish.callback_url');
    }

    /**
     * درخواست توکن پرداخت (Make Token)
     */
    public function makeToken(int $amount, string $paymentId = null): IkcResponse
    {
        $timestamp = now()->format('YmdHis');
        $requestId = uniqid('ikc_', true);

        // ساخت پاکت دیجیتال
        $envelope = EncryptionHelper::makeAuthenticationEnvelope(
            terminalId: $this->terminalId,
            passPhrase: $this->passPhrase,
            amount: $amount,
            publicKeyPem: $this->publicKey
        );

        $payload = [
            'request' => [
                'acceptorId'           => $this->acceptorId,
                'amount'               => $amount,
                'paymentId'            => $paymentId ?? $requestId,
                'requestId'            => $requestId,
                'requestTimestamp'     => $timestamp,
                'revertUri'            => $this->callbackUrl,
                'terminalId'           => $this->terminalId,
                'transactionType'      => 1000, // خرید
                'authenticationEnvelope' => $envelope,
            ],
        ];

        $response = $this->sendRequest('/api/v3/tokenization/make', $payload);
        return new IkcResponse($response);
    }

    /**
     * تأیید تراکنش (Confirm)
     */
    public function confirm(
        string $tokenIdentity,
        string $rrn,
        string $stan
    ): IkcResponse {
        $payload = [
            'request' => [
                'terminalId'                => $this->terminalId,
                'retrievalReferenceNumber'  => $rrn,
                'systemTraceAuditNumber'    => $stan,
                'tokenIdentity'             => $tokenIdentity,
            ],
        ];

        $response = $this->sendRequest('/api/v3/confirmation/purchase', $payload);
        return new IkcResponse($response);
    }

    /**
     * برگشت تراکنش (Reverse)
     */
    public function reverse(
        string $tokenIdentity,
        string $rrn,
        string $stan
    ): IkcResponse {
        $payload = [
            'request' => [
                'terminalId'                => $this->terminalId,
                'retrievalReferenceNumber'  => $rrn,
                'systemTraceAuditNumber'    => $stan,
                'tokenIdentity'             => $tokenIdentity,
            ],
        ];

        $response = $this->sendRequest('/api/v3/confirmation/reversePurchase', $payload);
        return new IkcResponse($response);
    }

    /**
     * استعلام تراکنش (Inquiry)
     */
    public function inquiry(string $rrn): IkcResponse
    {
        $payload = [
            'request' => [
                'terminalId'   => $this->terminalId,
                'passPhrase'   => $this->passPhrase,
                'findOption'   => 2,
                'findValue'    => $rrn,
            ],
        ];

        $response = $this->sendRequest('/api/v3/inquiry/single', $payload);
        return new IkcResponse($response);
    }

    /**
     * متد عمومی برای ارسال درخواست HTTP به API
     */
    protected function sendRequest(string $endpoint, array $payload): array
    {
        $url = $this->baseUrl . $endpoint;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 30,
        ]);

        $result = curl_exec($ch);
        $error  = curl_error($ch);
        curl_close($ch);

        if ($result === false) {
            throw new Exception("Connection error: {$error}");
        }

        $data = json_decode($result, true);
        if (!is_array($data)) {
            throw new Exception("Invalid response from IranKish: {$result}");
        }

        return $data;
    }
}
