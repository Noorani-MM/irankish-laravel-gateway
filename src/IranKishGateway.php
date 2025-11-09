<?php

namespace IranKish;

use IranKish\Contracts\GatewayContract;
use IranKish\Enums\ResponseCode;
use IranKish\Enums\TransactionType;
use IranKish\Exceptions\IranKishException;
use IranKish\Http\Client;
use IranKish\Support\DigitalEnvelope;

/**
 * IranKish gateway implementation.
 *
 * Standard flow:
 *   1. requestToken() → get token for payment
 *   2. redirect() or redirectData() → send user to IranKish IPG
 *   3. confirm() → verify transaction after return
 *   (optional) reverse() / inquiry()
 *
 * @see Tokenization API: https://www.irankish.com/App_Data_Public/IPG/IPG_TechnicalGuide.V9.pdf#page=16
 * @see Confirmation API: https://www.irankish.com/App_Data_Public/IPG/IPG_TechnicalGuide.V9.pdf#page=30
 * @see Inquiry API: https://www.irankish.com/App_Data_Public/IPG/IPG_TechnicalGuide.V9.pdf#page=37
 */
class IranKishGateway implements GatewayContract
{
    protected Client $http;

    public function __construct(protected array $config)
    {
        $this->http = new Client($config);
    }

    /**
     * Request payment token from IranKish.
     *
     * @param int $amount Transaction amount in Rials.
     * @param TransactionType|null $type Transaction type (default: Purchase).
     * @param array $options Optional extra parameters supported by the API:
     *  - billInfo (string|null): Used for bill payments.
     *  - paymentId (string|null): Your internal payment ID or order number.
     *  - requestId (string|null): Unique request identifier, max 20 chars (auto-generated if omitted).
     *  - requestTimestamp (int|null): Unix timestamp (default: time()).
     *  - revertUri (string|null): Callback URL after payment (defaults to config['revert_url']).
     *  - multiplexParameters (array|null): For split settlements; see DigitalEnvelope::buildMultiplexBase().
     *  - additionalParameters (array|null): Any custom data required by special integrations.
     *  - cmsPreservationId (string|null): For CMS-based payments.
     *  - asanShp (array|null): AsanShp wallet payment structure.
     *  - isbehdadtransaction (bool|null): Behdad bank payment flag.
     *
     * @return array{
     *   token: string|null,
     *   transactionType: string,
     *   raw: array
     * }
     *
     * @throws IranKishException on gateway or validation error.
     */
    public function requestToken(int $amount, ?TransactionType $type = null, array $options = []): array
    {
        $type ??= TransactionType::PURCHASE;

        // --- Multiplex (split) support: build base string if splits provided
        $baseString = null;
        if (!empty($options['multiplexParameters']) && is_array($options['multiplexParameters'])) {
            $baseString = DigitalEnvelope::buildMultiplexBase(
                $this->config['terminal_id'],
                $this->config['password'],
                $amount,
                $options['multiplexParameters']
            );
        }

        // --- Generate authentication envelope
        $auth = DigitalEnvelope::generate(
            $this->config['public_key'],
            $this->config['terminal_id'],
            $this->config['password'],
            $amount,
            $baseString
        );

        // --- Safe 20-char requestId (doc limit)
        $requestId = substr(
            $options['requestId'] ?? bin2hex(random_bytes(10)),
            0,
            20
        );

        $payload = [
            'authenticationEnvelope' => $auth,
            'request' => array_filter([
                'acceptorId'          => $this->config['acceptor_id'],
                'amount'              => $amount,
                'billInfo'            => $options['billInfo'] ?? null,
                'paymentId'           => $options['paymentId'] ?? null,
                'requestId'           => $requestId,
                'requestTimestamp'    => $options['requestTimestamp'] ?? time(),
                'revertUri'           => $options['revertUri'] ?? $this->config['revert_url'],
                'terminalId'          => $this->config['terminal_id'],
                'transactionType'     => $type->value,
                'multiplexParameters' => $options['multiplexParameters'] ?? null,
                'additionalParameters'=> $options['additionalParameters'] ?? null,
                'cmsPreservationId'   => $options['cmsPreservationId'] ?? null,
                'asanShp'             => $options['asanShp'] ?? null,
                'isbehdadtransaction' => $options['isbehdadtransaction'] ?? null,
            ], fn($v) => $v !== null),
        ];

        $resp = $this->http->post('tokenization/make', $payload);
        if (!$resp->ok()) {
            throw IranKishException::fromGateway('Gateway HTTP error during tokenization.');
        }

        $data = $resp->json();
        $code = $data['responseCode'] ?? null;

        if ($code !== ResponseCode::SUCCESS->value) {
            $desc = $data['description'] ?? 'Unknown error.';
            throw IranKishException::fromGateway($desc, $code);
        }

        return [
            'token' => $data['result']['token'] ?? null,
            'transactionType' => $type->value,
            'raw' => $data,
        ];
    }

    /**
     * Request a special token (used for special merchants).
     *
     * Same parameters as requestToken().
     */
    public function requestSpecialToken(int $amount, ?TransactionType $type = null, array $options = []): array
    {
        $type ??= TransactionType::PURCHASE;

        $auth = DigitalEnvelope::generate(
            $this->config['public_key'],
            $this->config['terminal_id'],
            $this->config['password'],
            $amount
        );

        $requestId = substr(
            $options['requestId'] ?? bin2hex(random_bytes(10)),
            0,
            20
        );

        $payload = [
            'authenticationEnvelope' => $auth,
            'request' => array_filter([
                'acceptorId'          => $this->config['acceptor_id'],
                'amount'              => $amount,
                'billInfo'            => $options['billInfo'] ?? null,
                'paymentId'           => $options['paymentId'] ?? null,
                'requestId'           => $requestId,
                'requestTimestamp'    => $options['requestTimestamp'] ?? time(),
                'revertUri'           => $options['revertUri'] ?? $this->config['revert_url'],
                'terminalId'          => $this->config['terminal_id'],
                'transactionType'     => $type->value,
                'additionalParameters'=> $options['additionalParameters'] ?? null,
                'cmsPreservationId'   => $options['cmsPreservationId'] ?? null,
            ], fn($v) => $v !== null),
        ];

        $resp = $this->http->post('tokenization/makeSpecial', $payload);
        if (!$resp->ok()) {
            throw IranKishException::fromGateway('Gateway HTTP error during special tokenization.');
        }

        $data = $resp->json();
        $code = $data['responseCode'] ?? null;

        if ($code !== ResponseCode::SUCCESS->value) {
            $desc = $data['description'] ?? 'Unknown error.';
            throw IranKishException::fromGateway($desc, $code);
        }

        return [
            'token' => $data['result']['token'] ?? null,
            'transactionType' => $type->value,
            'raw' => $data,
        ];
    }

    /**
     * Redirect user to IranKish payment page using HTML auto-submit form.
     *
     * @param string $token The token returned from requestToken()
     */
    public function redirect(string $token): void
    {
        $url = 'https://ikc.shaparak.ir/iuiv3/IPG/Index/';
        echo <<<HTML
        <form id="irankish-form" method="POST" action="{$url}">
            <input type="hidden" name="tokenIdentity" value="{$token}" />
        </form>
        <script>document.getElementById('irankish-form').submit();</script>
        HTML;
        exit;
    }

    /**
     * Return redirect information as array instead of executing it.
     *
     * @param string $token The token returned from requestToken()
     * @return array{
     *   transactionUrl: string,
     *   token: string,
     *   method: string,
     *   fields: array
     * }
     */
    public function redirectData(string $token): array
    {
        return [
            'transactionUrl' => 'https://ikc.shaparak.ir/iuiv3/IPG/Index/',
            'token' => $token,
            'method' => 'POST',
            'fields' => [
                'tokenIdentity' => $token,
            ],
        ];
    }

    /**
     * Confirm (verify) a payment after redirect return.
     *
     * @param string $token TokenIdentity value received in callback.
     * @param string $rrn Retrieval Reference Number (RRN) – 12-digit unique number assigned by IranKish.
     * @param string $stan System Trace Audit Number (STAN) – 6-digit trace number returned in callback.
     *
     * @return array Raw JSON response from IranKish confirmation API.
     *
     * @throws IranKishException if responseCode != 00 or request fails.
     */
    public function confirm(string $token, string $rrn, string $stan): array
    {
        $payload = [
            'terminalId' => $this->config['terminal_id'],
            'retrievalReferenceNumber' => $rrn,
            'systemTraceAuditNumber' => $stan,
            'tokenIdentity' => $token,
        ];

        $resp = $this->http->post('confirmation/purchase', $payload);
        if (!$resp->ok()) {
            throw IranKishException::fromGateway('Gateway HTTP error during confirmation.');
        }

        $data = $resp->json();
        if (($data['responseCode'] ?? null) !== ResponseCode::SUCCESS->value) {
            $desc = $data['description'] ?? 'Payment verification failed.';
            throw IranKishException::fromGateway($desc, $data['responseCode'] ?? null);
        }

        return $data;
    }

    /** @inheritdoc */
    public function reverse(string $token, string $rrn, string $stan): array
    {
        $payload = [
            'terminalId' => $this->config['terminal_id'],
            'retrievalReferenceNumber' => $rrn,
            'systemTraceAuditNumber' => $stan,
            'tokenIdentity' => $token,
        ];

        $resp = $this->http->post('confirmation/reversePurchase', $payload);
        if (!$resp->ok()) {
            throw IranKishException::fromGateway('Gateway HTTP error during reverse.');
        }

        return $resp->json();
    }

    /** @inheritdoc */
    public function inquiry(array $criteria): array
    {
        $payload = [
            'passPhrase' => $criteria['passPhrase'] ?? $this->config['password'],
            'terminalId' => $this->config['terminal_id'],
            'findOption' => $criteria['findOption'] ?? 2, // 1: RRN, 2: tokenIdentity, 3: requestId
        ];

        if ($payload['findOption'] === 1) {
            $payload['retrievalReferenceNumber'] = $criteria['retrievalReferenceNumber'] ?? null;
        } elseif ($payload['findOption'] === 2) {
            $payload['tokenIdentity'] = $criteria['tokenIdentity'] ?? null;
        } else {
            $payload['requestId'] = $criteria['requestId'] ?? null;
        }

        $resp = $this->http->post('inquiry/single', $payload);
        if (!$resp->ok()) {
            throw IranKishException::fromGateway('Gateway HTTP error during inquiry.');
        }

        return $resp->json();
    }
}
