<?php

declare(strict_types=1);

use Ux2Dev\Borica\Certificate\CertificateImporter;
use Ux2Dev\Borica\Exception\ConfigurationException;

test('fromPfxFile extracts private key and certificate', function () {
    $parts = CertificateImporter::fromPfxFile(
        __DIR__ . '/../fixtures/test_bundle.pfx',
        'testpass',
    );
    expect($parts['privateKey'])->toContain('-----BEGIN');
    expect($parts['privateKey'])->toContain('PRIVATE KEY');
    expect($parts['certificate'])->toContain('-----BEGIN CERTIFICATE-----');
});

test('fromPfxFile extracts from a legacy (RC2-40-CBC) PKCS#12 bundle', function () {
    // BORICA's real PFX/P12 files use pbeWithSHA1And40BitRC2-CBC, which
    // OpenSSL 3's default provider rejects — importer must fall back to the
    // `openssl` CLI with -legacy to read them.
    $parts = CertificateImporter::fromPfxFile(
        __DIR__ . '/../fixtures/test_bundle_legacy.pfx',
        'testpass',
    );
    expect($parts['privateKey'])->toContain('-----BEGIN');
    expect($parts['privateKey'])->toContain('PRIVATE KEY');
    expect($parts['certificate'])->toContain('-----BEGIN CERTIFICATE-----');
});

test('fromPfxFile throws on wrong passphrase', function () {
    CertificateImporter::fromPfxFile(
        __DIR__ . '/../fixtures/test_bundle.pfx',
        'wrong-password',
    );
})->throws(ConfigurationException::class);

test('fromPfxFile throws on missing file', function () {
    CertificateImporter::fromPfxFile('/nonexistent.pfx');
})->throws(ConfigurationException::class);

test('importToFiles writes key and cert to output directory', function () {
    $outputDir = sys_get_temp_dir() . '/borica-test-' . uniqid();
    try {
        $paths = CertificateImporter::importToFiles(
            pfxPath: __DIR__ . '/../fixtures/test_bundle.pfx',
            passphrase: 'testpass',
            outputDir: $outputDir,
        );
        expect(file_exists($paths['keyPath']))->toBeTrue();
        expect(file_exists($paths['certPath']))->toBeTrue();
        expect(file_get_contents($paths['keyPath']))->toContain('-----BEGIN');
        expect(file_get_contents($paths['certPath']))->toContain('-----BEGIN CERTIFICATE-----');
    } finally {
        if (is_dir($outputDir)) {
            array_map('unlink', glob("{$outputDir}/*") ?: []);
            rmdir($outputDir);
        }
    }
});
