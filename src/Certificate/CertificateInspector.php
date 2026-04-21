<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Certificate;

use DateTimeImmutable;
use DateTimeZone;
use Ux2Dev\Borica\Exception\ConfigurationException;

final class CertificateInspector
{
    public static function fromPem(string $pem): CertificateInfo
    {
        $parsed = @openssl_x509_parse($pem);

        if ($parsed === false) {
            throw new ConfigurationException(
                'Failed to parse certificate (not a valid PEM-encoded X.509 certificate)'
            );
        }

        return self::buildInfo($parsed);
    }

    public static function fromFile(string $path): CertificateInfo
    {
        if (!is_file($path)) {
            throw new ConfigurationException("Certificate file not found: {$path}");
        }
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new ConfigurationException("Cannot read certificate file: {$path}");
        }

        if (!str_contains($contents, '-----BEGIN')) {
            $contents = "-----BEGIN CERTIFICATE-----\n"
                . chunk_split(base64_encode($contents), 64, "\n")
                . "-----END CERTIFICATE-----\n";
        }

        return self::fromPem($contents);
    }

    /**
     * @param array<string, mixed> $parsed
     */
    private static function buildInfo(array $parsed): CertificateInfo
    {
        $notBefore = new DateTimeImmutable('@' . $parsed['validFrom_time_t'], new DateTimeZone('UTC'));
        $notAfter = new DateTimeImmutable('@' . $parsed['validTo_time_t'], new DateTimeZone('UTC'));

        return new CertificateInfo(
            notBefore: $notBefore,
            notAfter: $notAfter,
            subject: self::formatDn($parsed['subject'] ?? []),
            issuer: self::formatDn($parsed['issuer'] ?? []),
            serialNumber: (string) ($parsed['serialNumberHex'] ?? $parsed['serialNumber'] ?? ''),
        );
    }

    /**
     * @param array<string, string|array<string>> $dn
     */
    private static function formatDn(array $dn): string
    {
        $parts = [];
        foreach ($dn as $key => $value) {
            if (is_array($value)) {
                $value = implode('+', $value);
            }
            $parts[] = "{$key}={$value}";
        }
        return implode(', ', $parts);
    }
}
