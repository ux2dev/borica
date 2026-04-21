<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Certificate;

use Ux2Dev\Borica\Exception\ConfigurationException;

final class CertificateImporter
{
    /**
     * @return array{privateKey: string, certificate: string, extraCerts: array<int, string>}
     */
    public static function fromPfxFile(string $path, string $passphrase = ''): array
    {
        if (!is_file($path)) {
            throw new ConfigurationException("PFX file not found: {$path}");
        }
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new ConfigurationException("Cannot read PFX file: {$path}");
        }
        return self::fromPfxString($contents, $passphrase);
    }

    /**
     * @return array{privateKey: string, certificate: string, extraCerts: array<int, string>}
     */
    public static function fromPfxString(string $pfxContents, string $passphrase = ''): array
    {
        $certs = [];

        if (@openssl_pkcs12_read($pfxContents, $certs, $passphrase)) {
            return [
                'privateKey' => (string) ($certs['pkey'] ?? ''),
                'certificate' => (string) ($certs['cert'] ?? ''),
                'extraCerts' => array_values($certs['extracerts'] ?? []),
            ];
        }

        // BORICA issues PKCS#12 bundles using legacy ciphers (e.g. RC2-40-CBC)
        // that OpenSSL 3's default provider rejects as "unsupported". Fall back
        // to the `openssl` CLI with the legacy provider enabled.
        $nativeError = self::drainOpensslErrors();
        $legacy = self::extractViaLegacyBinary($pfxContents, $passphrase);

        if ($legacy !== null) {
            return $legacy;
        }

        throw new ConfigurationException(
            'Failed to read PFX file - check passphrase and file integrity. '
            . ($nativeError !== '' ? "openssl error: {$nativeError}" : '')
        );
    }

    /**
     * @return array{keyPath: string, certPath: string}
     */
    public static function importToFiles(
        string $pfxPath,
        string $passphrase,
        string $outputDir,
        string $keyFilename = 'private.key',
        string $certFilename = 'certificate.pem',
    ): array {
        $parts = self::fromPfxFile($pfxPath, $passphrase);

        if (!is_dir($outputDir) && !mkdir($outputDir, 0700, true) && !is_dir($outputDir)) {
            throw new ConfigurationException("Cannot create output directory: {$outputDir}");
        }

        $keyPath = rtrim($outputDir, '/') . '/' . $keyFilename;
        $certPath = rtrim($outputDir, '/') . '/' . $certFilename;

        if (file_put_contents($keyPath, $parts['privateKey']) === false) {
            throw new ConfigurationException("Cannot write private key to: {$keyPath}");
        }
        chmod($keyPath, 0600);

        if (file_put_contents($certPath, $parts['certificate']) === false) {
            throw new ConfigurationException("Cannot write certificate to: {$certPath}");
        }
        chmod($certPath, 0644);

        return ['keyPath' => $keyPath, 'certPath' => $certPath];
    }

    /**
     * @return array{privateKey: string, certificate: string, extraCerts: array<int, string>}|null
     */
    private static function extractViaLegacyBinary(string $pfxContents, string $passphrase): ?array
    {
        $openssl = self::findOpensslBinary();
        if ($openssl === null) {
            return null;
        }

        $tempPfx = tempnam(sys_get_temp_dir(), 'borica_pfx_');
        if ($tempPfx === false) {
            return null;
        }

        try {
            if (file_put_contents($tempPfx, $pfxContents) === false) {
                return null;
            }

            $cmd = [$openssl, 'pkcs12', '-in', $tempPfx, '-nodes', '-passin', 'stdin'];
            if (self::opensslMajorVersion($openssl) >= 3) {
                array_splice($cmd, 2, 0, '-legacy');
            }

            [$exit, $stdout] = self::runProcess($cmd, $passphrase);

            if ($exit !== 0 || $stdout === '') {
                return null;
            }

            return self::parsePemBundle($stdout);
        } finally {
            @unlink($tempPfx);
        }
    }

    /**
     * @return array{privateKey: string, certificate: string, extraCerts: array<int, string>}|null
     */
    private static function parsePemBundle(string $output): ?array
    {
        preg_match_all(
            '/-----BEGIN ([A-Z0-9 ]+?)-----.+?-----END \1-----/s',
            $output,
            $matches,
            PREG_SET_ORDER,
        );

        $privateKey = null;
        $certificates = [];

        foreach ($matches as [$block, $type]) {
            if ($privateKey === null && str_contains($type, 'PRIVATE KEY')) {
                $privateKey = $block . "\n";
            } elseif ($type === 'CERTIFICATE') {
                $certificates[] = $block . "\n";
            }
        }

        if ($privateKey === null || $certificates === []) {
            return null;
        }

        return [
            'privateKey' => $privateKey,
            'certificate' => $certificates[0],
            'extraCerts' => array_slice($certificates, 1),
        ];
    }

    private static function findOpensslBinary(): ?string
    {
        $candidates = PHP_OS_FAMILY === 'Windows'
            ? ['openssl.exe', 'C:\\Program Files\\OpenSSL-Win64\\bin\\openssl.exe']
            : ['/usr/bin/openssl', '/usr/local/bin/openssl', '/opt/homebrew/bin/openssl', '/opt/local/bin/openssl', 'openssl'];

        foreach ($candidates as $candidate) {
            [$exit] = self::runProcess([$candidate, 'version']);
            if ($exit === 0) {
                return $candidate;
            }
        }
        return null;
    }

    private static function opensslMajorVersion(string $binary): int
    {
        [$exit, $stdout] = self::runProcess([$binary, 'version']);
        if ($exit !== 0) {
            return 1;
        }
        return preg_match('/OpenSSL\s+(\d+)\./', $stdout, $m) ? (int) $m[1] : 1;
    }

    /**
     * Runs a process via proc_open (no shell) and returns exit/stdout/stderr.
     *
     * @param  array<int, string>                 $cmd
     * @return array{0: int, 1: string, 2: string}
     */
    private static function runProcess(array $cmd, string $stdin = ''): array
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $proc = @proc_open($cmd, $descriptors, $pipes);
        if (!is_resource($proc)) {
            return [-1, '', ''];
        }

        if ($stdin !== '') {
            fwrite($pipes[0], $stdin);
        }
        fclose($pipes[0]);

        $stdout = (string) stream_get_contents($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [proc_close($proc), $stdout, $stderr];
    }

    private static function drainOpensslErrors(): string
    {
        $msgs = [];
        while ($m = openssl_error_string()) {
            $msgs[] = $m;
        }
        return implode('; ', $msgs);
    }
}
