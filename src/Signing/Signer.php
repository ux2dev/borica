<?php
declare(strict_types=1);
namespace Ux2Dev\Borica\Signing;

use Ux2Dev\Borica\Exception\SigningException;

class Signer
{
    public function sign(string $data, string $privateKeyPem, ?string $passphrase = null): string
    {
        $key = openssl_pkey_get_private($privateKeyPem, $passphrase ?? '');
        if ($key === false) {
            throw new SigningException('Failed to load private key');
        }
        $result = openssl_sign($data, $signature, $key, OPENSSL_ALGO_SHA256);
        if ($result === false) {
            throw new SigningException('Failed to sign data');
        }
        return strtoupper(bin2hex($signature));
    }

    public function verify(string $data, string $pSign, string $publicKeyPem): bool
    {
        if (strlen($pSign) !== 512) {
            return false;
        }

        $key = openssl_pkey_get_public($publicKeyPem);
        if ($key === false) {
            throw new SigningException('Failed to load public key');
        }
        $signature = hex2bin(strtolower($pSign));
        if ($signature === false) {
            return false;
        }
        $result = openssl_verify($data, $signature, $key, OPENSSL_ALGO_SHA256);
        if ($result === -1) {
            throw new SigningException('Signature verification error');
        }
        return $result === 1;
    }
}
