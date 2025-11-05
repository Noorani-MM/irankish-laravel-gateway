<?php

namespace IranKish\Helpers;

class EncryptionHelper
{
    /**
     * Build IKC authentication envelope using AES-128-CBC + RSA public key wrapping.
     *
     * @param string $publicKey  PEM content or absolute path to PEM file
     * @param string $terminalId
     * @param string $password
     * @param int    $amount     Amount in Rials (as integers)
     *
     * @return array{data:string, iv:string}
     *
     * @throws \RuntimeException on invalid public key
     */
    public static function generateEnvelope(string $publicKey, string $terminalId, string $password, int $amount): array
    {
        // According to IKC docs, payload is terminalId + password + zero-padded amount (12 digits) + '00'
        $data = $terminalId . $password . str_pad((string)$amount, 12, '0', STR_PAD_LEFT) . '00';
        $data = hex2bin($data);

        $aesKey = openssl_random_pseudo_bytes(16);
        $cipher = 'AES-128-CBC';
        $ivLen  = openssl_cipher_iv_length($cipher);
        $iv     = openssl_random_pseudo_bytes($ivLen);

        $ciphertextRaw = openssl_encrypt($data, $cipher, $aesKey, OPENSSL_RAW_DATA, $iv);
        $hmac          = hash('sha256', $ciphertextRaw, true);

        $pubRes = self::loadPublicKey($publicKey);
        $sealed = '';
        if (!openssl_public_encrypt($aesKey . $hmac, $sealed, $pubRes, OPENSSL_PKCS1_OAEP_PADDING)) {
            throw new \RuntimeException('Failed to encrypt AES key using provided public key');
        }

        return [
            'data' => bin2hex($sealed),
            'iv'   => bin2hex($iv),
        ];
    }

    /**
     * Accepts either raw PEM content, or an absolute file path to a PEM file.
     *
     * @param string $publicKey PEM string or file path
     * @return resource|\OpenSSLAsymmetricKey
     */
    protected static function loadPublicKey(string $publicKey)
    {
        $pem = self::looksLikePem($publicKey) ? $publicKey : (is_file($publicKey) ? file_get_contents($publicKey) : $publicKey);
        $res = openssl_pkey_get_public($pem);
        if (!$res) {
            throw new \RuntimeException('Invalid public key content');
        }
        return $res;
    }

    protected static function looksLikePem(string $value): bool
    {
        return str_contains($value, 'BEGIN PUBLIC KEY') || str_contains($value, 'BEGIN RSA PUBLIC KEY');
    }
}
