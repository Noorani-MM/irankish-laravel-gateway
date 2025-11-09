<?php

namespace IranKish\Support;

/**
 * Builds the Authentication Envelope using AES-128-CBC + RSA (public key).
 * Spec: AES-128-CBC, PKCS7 padding; IV length per OpenSSL.
 * Steps summarized from the official guide:
 * @see https://www.irankish.com/App_Data_Public/IPG/IPG_TechnicalGuide.V9.pdf#page=23
 */
class DigitalEnvelope
{
    /**
     * Generate envelope for standard purchase/bill (no multiplex string build here).
     * For advanced flows (multiplex), the base string must follow the spec (IBAN normalization).
     *
     * @param string $publicKey   PEM-encoded RSA public key
     * @param string $terminalId
     * @param string $password
     * @param int    $amount
     * @param string|null $baseString Optional prebuilt base string to encrypt (for multiplex).
     * @return array ['data' => hex, 'iv' => hex]
     */
    public static function generate(string $publicKey, string $terminalId, string $password, int $amount, ?string $baseString = null): array
    {
        // Build base data as specified: terminalId + password + amount(12 left-padded) + "00"
        $base = $baseString ?: ($terminalId . $password . str_pad((string)$amount, 12, '0', STR_PAD_LEFT) . '00');

        // Convert to binary; we keep it simple via utf8->bin
        $binary = $base; // base is ASCII digits; OpenSSL expects raw binary

        // Generate AES key and IV
        $aesKey = openssl_random_pseudo_bytes(16);
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('AES-128-CBC'));

        // Encrypt base with AES-128-CBC (RAW output)
        $cipherRaw = openssl_encrypt($binary, 'AES-128-CBC', $aesKey, OPENSSL_RAW_DATA, $iv);

        // HMAC SHA256 over ciphertext, raw binary
        $hmac = hash('sha256', $cipherRaw, true);

        // RSA encrypt (AES key + HMAC) using public key
        $encrypted = '';
        openssl_public_encrypt($aesKey . $hmac, $encrypted, $publicKey);

        return [
            'data' => bin2hex($encrypted),
            'iv'   => bin2hex($iv),
        ];
    }

    /**
     * Build multiplex base string for split settlement.
     * Replace 'IR' with '2718' at the beginning of IBAN per guide, then concatenate amounts.
     *
     * @param string $terminalId
     * @param string $password
     * @param int $totalAmount
     * @param array $splits [['iban' => 'IRxx...', 'amount' => 123], ...]
     * @return string Ready-to-encrypt base string
     *
     * @see example in guide: https://www.irankish.com/App_Data_Public/IPG/IPG_TechnicalGuide.V9.pdf#page=24
     */
    public static function buildMultiplexBase(string $terminalId, string $password, int $totalAmount, array $splits): string
    {
        $parts = [];
        foreach ($splits as $row) {
            $iban = $row['iban'];
            // Per doc: replace leading 'IR' by '2718'
            if (str_starts_with($iban, 'IR')) {
                $iban = '2718' . substr($iban, 2);
            }
            $parts[] = $iban . str_pad((string)$row['amount'], 12, '0', STR_PAD_LEFT);
        }

        // format: terminalId + password + totalAmount(12) + "01" + [iban+amount...]...
        return $terminalId
            . $password
            . str_pad((string)$totalAmount, 12, '0', STR_PAD_LEFT)
            . '01'
            . implode('', $parts);
    }
}
