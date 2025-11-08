<?php

namespace IranKish;

use IranKish\Helpers\EncryptionHelper;
use IranKish\Support\IkcResponse;

/**
 * IranKish payment gateway service for Laravel.
 *
 * This service:
 *  - Builds the authentication envelope (AES + RSA) as required by IKC
 *  - Requests a payment token and returns a controller-friendly result (url, ok, message, status)
 *  - Confirms the payment and returns a normalized result (ok, message, status, data)
 */
class IranKish
{
    /** @var array */
    protected $config;

    public function __construct(array $config = [])
    {
        $this->config = $config ?: config('irankish', []);
    }

    /**
     * Request payment token and build a redirect URL for the user to be sent to the IKC gateway.
     *
     * @param int         $amount     Amount in Rials (integer)
     * @param string|null $billInfo   Optional bill info (free-form per IKC docs)
     * @param string|null $paymentId  Optional internal payment ID/order ID
     *
     * @return array{url:?string, ok:bool, message:?string, status:?string}
     *
     * Behavior:
     *  - On success (responseCode=00), returns ['ok'=>true, 'status'=>'00', 'message'=>description, 'url'=>PAYMENT_BASE?token=...]
     *  - On failure, returns ['ok'=>false, 'status'=>responseCode|NETWORK_ERROR, 'message'=>description|error, 'url'=>null]
     */
    public function requestPayment(int $amount, $billInfo = null, $paymentId = null): array
    {
        try {
            $envelope = EncryptionHelper::generateEnvelope(
                $this->cfg('public_key'),
                $this->cfg('terminal_id'),
                $this->cfg('password'),
                $amount
            );

            $payload = [
                'request' => [
                    'acceptorId'        => $this->cfg('acceptor_id'),
                    'amount'            => $amount,
                    'billInfo'          => $billInfo,
                    'paymentId'         => $paymentId,
                    'requestId'         => uniqid('', true),
                    'requestTimestamp'  => time(),
                    'revertUri'         => $this->cfg('callback_url'),
                    'terminalId'        => $this->cfg('terminal_id'),
                    'transactionType'   => 'Purchase',
                ],
                'authenticationEnvelope' => $envelope,
            ];

            $raw   = $this->httpPost($this->ep('make_token'), $payload);
            $ikc   = new IkcResponse($raw);
            $ok    = $ikc->ok();
            $url   = null;

            if ($ok) {
                $token = $ikc->result['token'] ?? null;
                if ($token) {
                    $url = $this->buildPaymentUrl($token);
                } else {
                    // Token missing → treat as failure even if responseCode says 00
                    $ok = false;
                    $ikc->responseCode = $ikc->responseCode ?? 'NO_TOKEN';
                    $ikc->description  = $ikc->description  ?? 'Token missing in IKC response';
                }
            }

            return [
                'ok'      => $ok,
                'status'  => $ikc->responseCode,
                'message' => $ikc->description,
                'url'     => $url,
                'token'   => $ikc->result['token'],
            ];
        } catch (\Throwable $e) {
            return [
                'ok'      => false,
                'status'  => 'NETWORK_ERROR',
                'message' => $e->getMessage(),
                'url'     => null,
                'token'   => null,
            ];
        }
    }

    /**
     * Confirm a payment using IKC confirmation endpoint.
     *
     * @param string $token                     TokenIdentity (from IKC callback POST)
     * @param string $retrievalReferenceNumber  RRN (from IKC callback POST)
     * @param string $systemTraceAuditNumber    STAN (from IKC callback POST)
     *
     * @return array{ok:bool, message:?string, status:?string, data:array}
     *
     * Behavior:
     *  - Returns raw IKC response in 'data' to keep full compatibility with their fields
     *  - 'ok' is true when responseCode === '00'
     */
    public function confirm(string $token, string $retrievalReferenceNumber, string $systemTraceAuditNumber): array
    {
        $payload = [
            'terminalId'               => $this->cfg('terminal_id'),
            'retrievalReferenceNumber' => $retrievalReferenceNumber,
            'systemTraceAuditNumber'   => $systemTraceAuditNumber,
            'tokenIdentity'            => $token,
        ];

        try {
            $raw  = $this->httpPost($this->ep('confirm'), $payload);
            $ikc  = new IkcResponse($raw);

            return [
                'ok'      => $ikc->ok(),
                'status'  => $ikc->responseCode,
                'message' => $ikc->description,
                'data'    => $ikc->raw,
            ];
        } catch (\Throwable $e) {
            return [
                'ok'      => false,
                'status'  => 'NETWORK_ERROR',
                'message' => $e->getMessage(),
                'data'    => [],
            ];
        }
    }

    /**
     * Optional helper: build a Laravel RedirectResponse to IKC (if you want the service to handle redirect fully).
     * Usage:
     *   return app(IranKish::class)->redirectToGateway($token);
     */
    public function redirectToGateway(string $token)
    {
        $url = $this->buildPaymentUrl($token);
        return redirect()->away($url);
    }

    // -------------------- Internals --------------------

    protected function httpPost(string $url, array $data): array
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $json,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($json),
            ],
            CURLOPT_CONNECTTIMEOUT => (int)($this->cfg('http.connect_timeout') ?? 10),
            CURLOPT_TIMEOUT        => (int)($this->cfg('http.timeout') ?? 20),
        ]);

        if ($this->cfg('http.verify_ssl', true)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }

        $result = curl_exec($ch);
        if ($result === false) {
            $err = curl_error($ch) ?: 'cURL error';
            curl_close($ch);
            throw new \RuntimeException($err);
        }
        curl_close($ch);

        $decoded = json_decode($result, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('Invalid JSON from IKC');
        }

        return $decoded;
    }

    protected function buildPaymentUrl(string $token): string
    {
        $base = rtrim($this->ep('payment_base'), '/');
        // Most integrations expect ?token=... at the end. Keep flexible if your base ends with 'Index'.
        $separator = str_contains($base, '?') ? '&' : '?';
        return $base . $separator . 'token=' . urlencode($token);
    }

    protected function cfg(string $key, $default = null)
    {
        // dot-notation: e.g. 'endpoints.make_token'
        $value = data_get($this->config, $key, null);
        if ($value === null) {
            $value = data_get(config('irankish', []), $key, $default);
        }
        return $value ?? $default;
    }

    protected function ep(string $name): string
    {
        return (string)$this->cfg('endpoints.' . $name);
    }
}
