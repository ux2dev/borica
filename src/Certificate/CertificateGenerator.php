<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Certificate;

use Ux2Dev\Borica\Enum\Environment;
use Ux2Dev\Borica\Exception\SigningException;

final class CertificateGenerator
{
    /**
     * Generate an RSA private key and a Certificate Signing Request (CSR)
     * per BORICA P-OM-41 spec (sections 5.2, 5.3, 5.4).
     *
     * The CSR file is uploaded to Merchant Portal:
     *  - Test:       https://3dsgate-dev.borica.bg/mwp_cert
     *  - Production: https://3dsgate.borica.bg/mwp/static/
     *
     * @param string $terminalId    TID provided by the acquirer bank (8 alphanumeric chars)
     * @param Environment $environment  Development (T) or Production (P)
     * @param string $commonName    Merchant domain without protocol (e.g. "merchantdomain.bg")
     * @param string $organizationName  Legal company name
     * @param string $localityName  City (e.g. "Sofia")
     * @param string $stateOrProvinceName Region/state (e.g. "Sofia")
     * @param string $emailAddress  Contact email
     * @param string $countryCode   ISO 3166-1 alpha-2 (default: "BG")
     * @param string|null $passphrase  Optional passphrase to encrypt the private key (recommended for production)
     * @param int $keyBits          RSA key size in bits (default: 2048)
     */
    public static function generate(
        string $terminalId,
        Environment $environment,
        string $commonName,
        string $organizationName,
        string $localityName,
        string $stateOrProvinceName,
        string $emailAddress,
        string $countryCode = 'BG',
        ?string $passphrase = null,
        int $keyBits = 2048,
    ): CertificateResult {
        self::validate($terminalId, $commonName, $organizationName, $localityName, $stateOrProvinceName, $emailAddress, $countryCode, $keyBits);

        $key = openssl_pkey_new([
            'private_key_bits' => $keyBits,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if ($key === false) {
            throw new SigningException('Failed to generate RSA key pair: ' . openssl_error_string());
        }

        $privateKeyPem = '';
        $cipher = $passphrase !== null ? 'aes-256-cbc' : null;
        $exported = openssl_pkey_export($key, $privateKeyPem, $passphrase, $cipher ? ['encrypt_key_cipher' => $cipher] : []);

        if ($exported === false) {
            throw new SigningException('Failed to export private key: ' . openssl_error_string());
        }

        $dn = [
            'commonName' => $commonName,
            'organizationalUnitName' => $terminalId,
            'organizationName' => $organizationName,
            'localityName' => $localityName,
            'stateOrProvinceName' => $stateOrProvinceName,
            'countryName' => $countryCode,
            'emailAddress' => $emailAddress,
        ];

        $csr = openssl_csr_new($dn, $key);

        if ($csr === false) {
            throw new SigningException('Failed to generate CSR: ' . openssl_error_string());
        }

        $csrPem = '';
        if (openssl_csr_export($csr, $csrPem) === false) {
            throw new SigningException('Failed to export CSR: ' . openssl_error_string());
        }

        $suffix = $environment === Environment::Development ? 'T' : 'P';
        $date = date('Ymd');
        $privateKeyFilename = "privatekeyname_{$suffix}.key";
        $csrFilename = "{$terminalId}_{$date}_{$suffix}.csr";

        return new CertificateResult($privateKeyPem, $csrPem, $privateKeyFilename, $csrFilename);
    }

    private static function validate(
        string $terminalId,
        string $commonName,
        string $organizationName,
        string $localityName,
        string $stateOrProvinceName,
        string $emailAddress,
        string $countryCode,
        int $keyBits,
    ): void {
        if (!preg_match('/^[A-Za-z0-9]{8}$/', $terminalId)) {
            throw new SigningException('Terminal ID must be exactly 8 alphanumeric characters');
        }

        if ($commonName === '' || str_contains($commonName, '://')) {
            throw new SigningException('Common name must be a domain without protocol (e.g. "merchantdomain.bg")');
        }

        if ($organizationName === '') {
            throw new SigningException('Organization name must not be empty');
        }

        if ($localityName === '') {
            throw new SigningException('Locality name must not be empty');
        }

        if ($stateOrProvinceName === '') {
            throw new SigningException('State or province name must not be empty');
        }

        if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
            throw new SigningException('A valid email address is required');
        }

        if (!preg_match('/^[A-Z]{2}$/', $countryCode)) {
            throw new SigningException('Country code must be a 2-letter uppercase ISO code (e.g. "BG")');
        }

        if ($keyBits < 2048) {
            throw new SigningException('Key size must be at least 2048 bits');
        }
    }
}
