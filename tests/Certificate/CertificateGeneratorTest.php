<?php

declare(strict_types=1);

use Ux2Dev\Borica\Certificate\CertificateGenerator;
use Ux2Dev\Borica\Certificate\CertificateResult;
use Ux2Dev\Borica\Enum\Environment;
use Ux2Dev\Borica\Exception\SigningException;

test('generates valid RSA private key and CSR', function () {
    $result = CertificateGenerator::generate(
        terminalId: 'V1800001',
        environment: Environment::Development,
        commonName: 'merchantdomain.bg',
        organizationName: 'Test Company Ltd.',
        localityName: 'Sofia',
        stateOrProvinceName: 'Sofia',
        emailAddress: 'admin@merchantdomain.bg',
    );

    expect($result)->toBeInstanceOf(CertificateResult::class);
    expect($result->privateKey)->toStartWith('-----BEGIN PRIVATE KEY-----');
    expect($result->csr)->toStartWith('-----BEGIN CERTIFICATE REQUEST-----');
});

test('CSR contains correct subject fields', function () {
    $result = CertificateGenerator::generate(
        terminalId: 'V1800001',
        environment: Environment::Development,
        commonName: 'merchantdomain.bg',
        organizationName: 'Test Company Ltd.',
        localityName: 'Sofia',
        stateOrProvinceName: 'Sofia-grad',
        emailAddress: 'admin@merchantdomain.bg',
    );

    $parsed = openssl_csr_get_subject($result->csr);

    expect($parsed['CN'])->toBe('merchantdomain.bg');
    expect($parsed['OU'])->toBe('V1800001');
    expect($parsed['O'])->toBe('Test Company Ltd.');
    expect($parsed['L'])->toBe('Sofia');
    expect($parsed['ST'])->toBe('Sofia-grad');
    expect($parsed['C'])->toBe('BG');
    expect($parsed['emailAddress'])->toBe('admin@merchantdomain.bg');
});

test('private key can sign and CSR public key can verify', function () {
    $result = CertificateGenerator::generate(
        terminalId: 'V1800001',
        environment: Environment::Development,
        commonName: 'merchantdomain.bg',
        organizationName: 'Test Company Ltd.',
        localityName: 'Sofia',
        stateOrProvinceName: 'Sofia',
        emailAddress: 'admin@merchantdomain.bg',
    );

    $key = openssl_pkey_get_private($result->privateKey);
    expect($key)->not->toBeFalse();

    $data = 'test signing data';
    openssl_sign($data, $signature, $key, OPENSSL_ALGO_SHA256);

    $csrResource = openssl_csr_get_public_key($result->csr);
    $verified = openssl_verify($data, $signature, $csrResource, OPENSSL_ALGO_SHA256);
    expect($verified)->toBe(1);
});

test('test environment uses _T suffix in filenames', function () {
    $result = CertificateGenerator::generate(
        terminalId: 'V1800001',
        environment: Environment::Development,
        commonName: 'merchantdomain.bg',
        organizationName: 'Test Company Ltd.',
        localityName: 'Sofia',
        stateOrProvinceName: 'Sofia',
        emailAddress: 'admin@merchantdomain.bg',
    );

    $date = date('Ymd');
    expect($result->privateKeyFilename)->toBe('privatekeyname_T.key');
    expect($result->csrFilename)->toBe("V1800001_{$date}_T.csr");
});

test('production environment uses _P suffix in filenames', function () {
    $result = CertificateGenerator::generate(
        terminalId: 'V1800001',
        environment: Environment::Production,
        commonName: 'merchantdomain.bg',
        organizationName: 'Test Company Ltd.',
        localityName: 'Sofia',
        stateOrProvinceName: 'Sofia',
        emailAddress: 'admin@merchantdomain.bg',
    );

    $date = date('Ymd');
    expect($result->privateKeyFilename)->toBe('privatekeyname_P.key');
    expect($result->csrFilename)->toBe("V1800001_{$date}_P.csr");
});

test('passphrase encrypts the private key', function () {
    $result = CertificateGenerator::generate(
        terminalId: 'V1800001',
        environment: Environment::Production,
        commonName: 'merchantdomain.bg',
        organizationName: 'Test Company Ltd.',
        localityName: 'Sofia',
        stateOrProvinceName: 'Sofia',
        emailAddress: 'admin@merchantdomain.bg',
        passphrase: 'secretpass',
    );

    expect($result->privateKey)->toContain('ENCRYPTED');

    // Cannot load without passphrase
    expect(openssl_pkey_get_private($result->privateKey))->toBeFalse();

    // Can load with correct passphrase
    expect(openssl_pkey_get_private($result->privateKey, 'secretpass'))->not->toBeFalse();
});

test('saveToDirectory writes both files', function () {
    $result = CertificateGenerator::generate(
        terminalId: 'V1800001',
        environment: Environment::Development,
        commonName: 'merchantdomain.bg',
        organizationName: 'Test Company Ltd.',
        localityName: 'Sofia',
        stateOrProvinceName: 'Sofia',
        emailAddress: 'admin@merchantdomain.bg',
    );

    $tmpDir = sys_get_temp_dir() . '/borica_test_' . uniqid();
    mkdir($tmpDir);

    $result->saveToDirectory($tmpDir);

    expect(file_exists($tmpDir . '/' . $result->privateKeyFilename))->toBeTrue();
    expect(file_exists($tmpDir . '/' . $result->csrFilename))->toBeTrue();
    expect(file_get_contents($tmpDir . '/' . $result->privateKeyFilename))->toBe($result->privateKey);
    expect(file_get_contents($tmpDir . '/' . $result->csrFilename))->toBe($result->csr);

    // Cleanup
    unlink($tmpDir . '/' . $result->privateKeyFilename);
    unlink($tmpDir . '/' . $result->csrFilename);
    rmdir($tmpDir);
});

test('throws on invalid terminal ID', function () {
    CertificateGenerator::generate(
        terminalId: 'short',
        environment: Environment::Development,
        commonName: 'merchantdomain.bg',
        organizationName: 'Test',
        localityName: 'Sofia',
        stateOrProvinceName: 'Sofia',
        emailAddress: 'admin@test.bg',
    );
})->throws(SigningException::class, 'Terminal ID must be exactly 8 alphanumeric characters');

test('throws on common name with protocol', function () {
    CertificateGenerator::generate(
        terminalId: 'V1800001',
        environment: Environment::Development,
        commonName: 'https://merchantdomain.bg',
        organizationName: 'Test',
        localityName: 'Sofia',
        stateOrProvinceName: 'Sofia',
        emailAddress: 'admin@test.bg',
    );
})->throws(SigningException::class, 'Common name must be a domain without protocol');

test('throws on empty common name', function () {
    CertificateGenerator::generate(
        terminalId: 'V1800001',
        environment: Environment::Development,
        commonName: '',
        organizationName: 'Test',
        localityName: 'Sofia',
        stateOrProvinceName: 'Sofia',
        emailAddress: 'admin@test.bg',
    );
})->throws(SigningException::class, 'Common name must be a domain without protocol');

test('throws on empty organization name', function () {
    CertificateGenerator::generate(
        terminalId: 'V1800001',
        environment: Environment::Development,
        commonName: 'merchantdomain.bg',
        organizationName: '',
        localityName: 'Sofia',
        stateOrProvinceName: 'Sofia',
        emailAddress: 'admin@test.bg',
    );
})->throws(SigningException::class, 'Organization name must not be empty');

test('throws on empty locality', function () {
    CertificateGenerator::generate(
        terminalId: 'V1800001',
        environment: Environment::Development,
        commonName: 'merchantdomain.bg',
        organizationName: 'Test',
        localityName: '',
        stateOrProvinceName: 'Sofia',
        emailAddress: 'admin@test.bg',
    );
})->throws(SigningException::class, 'Locality name must not be empty');

test('throws on empty state', function () {
    CertificateGenerator::generate(
        terminalId: 'V1800001',
        environment: Environment::Development,
        commonName: 'merchantdomain.bg',
        organizationName: 'Test',
        localityName: 'Sofia',
        stateOrProvinceName: '',
        emailAddress: 'admin@test.bg',
    );
})->throws(SigningException::class, 'State or province name must not be empty');

test('throws on invalid email', function () {
    CertificateGenerator::generate(
        terminalId: 'V1800001',
        environment: Environment::Development,
        commonName: 'merchantdomain.bg',
        organizationName: 'Test',
        localityName: 'Sofia',
        stateOrProvinceName: 'Sofia',
        emailAddress: 'not-an-email',
    );
})->throws(SigningException::class, 'A valid email address is required');

test('throws on invalid country code', function () {
    CertificateGenerator::generate(
        terminalId: 'V1800001',
        environment: Environment::Development,
        commonName: 'merchantdomain.bg',
        organizationName: 'Test',
        localityName: 'Sofia',
        stateOrProvinceName: 'Sofia',
        emailAddress: 'admin@test.bg',
        countryCode: 'Bulgaria',
    );
})->throws(SigningException::class, 'Country code must be a 2-letter uppercase ISO code');

test('throws on key size below 2048', function () {
    CertificateGenerator::generate(
        terminalId: 'V1800001',
        environment: Environment::Development,
        commonName: 'merchantdomain.bg',
        organizationName: 'Test',
        localityName: 'Sofia',
        stateOrProvinceName: 'Sofia',
        emailAddress: 'admin@test.bg',
        keyBits: 1024,
    );
})->throws(SigningException::class, 'Key size must be at least 2048 bits');
