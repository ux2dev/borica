<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayCheckout\Http;

use Ux2Dev\Borica\Exception\SignatureException;

/**
 * Produces a BORICA-compatible JWS over a JSON body using RS256.
 *
 * Header format: {"x5c":["<base64 leaf cert>"],"alg":"RS256"}
 * Signing input: base64url(header) + "." + base64url(body)
 * Transmitted as: base64url(header) + ".." + base64url(signature)
 * (payload segment stripped for detached mode in X-JWS-Signature header)
 */
final class JwsSigner
{
    /**
     * Produce a detached JWS for the given JSON body.
     */
    public function sign(string $jsonBody, string $privateKeyPem, string $certificatePem, ?string $passphrase = null): string
    {
        $header = [
            'x5c' => [$this->certPemToBase64($certificatePem)],
            'alg' => 'RS256',
        ];
        $headerB64 = $this->base64UrlEncode(
            json_encode($header, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)
        );

        $signingInput = $headerB64 . '.' . $this->base64UrlEncode($jsonBody);

        $key = $passphrase === null
            ? openssl_pkey_get_private($privateKeyPem)
            : openssl_pkey_get_private($privateKeyPem, $passphrase);

        if ($key === false) {
            throw new SignatureException(
                'Failed to load private key for JWS signing: ' . openssl_error_string()
            );
        }

        $signature = '';
        $ok = openssl_sign($signingInput, $signature, $key, OPENSSL_ALGO_SHA256);

        if ($ok !== true) {
            throw new SignatureException(
                'JWS signing failed: ' . openssl_error_string()
            );
        }

        return $headerB64 . '..' . $this->base64UrlEncode($signature);
    }

    /**
     * Strip PEM headers and all whitespace, returning standard base64 content.
     */
    private function certPemToBase64(string $pem): string
    {
        // Remove -----BEGIN ...------ and -----END ...------ lines and all whitespace
        $stripped = preg_replace('/-----BEGIN[^-]*-----/', '', $pem);
        $stripped = preg_replace('/-----END[^-]*-----/', '', $stripped ?? '');
        return preg_replace('/\s+/', '', $stripped ?? '');
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
