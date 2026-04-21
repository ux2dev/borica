<?php

declare(strict_types=1);

use Ux2Dev\Borica\Certificate\CertificateInspector;
use Ux2Dev\Borica\Exception\ConfigurationException;

test('fromFile parses PEM certificate', function () {
    $info = CertificateInspector::fromFile(__DIR__ . '/../fixtures/test_certificate.pem');
    expect($info->subject)->toContain('CN=test.example.com');
    expect($info->issuer)->toContain('CN=test.example.com'); // self-signed
    expect($info->serialNumber)->not->toBe('');
    expect($info->notAfter > $info->notBefore)->toBeTrue();
});

test('isExpired returns false for valid cert', function () {
    $info = CertificateInspector::fromFile(__DIR__ . '/../fixtures/test_certificate.pem');
    expect($info->isExpired())->toBeFalse();
});

test('isExpired returns true for past cert', function () {
    $info = CertificateInspector::fromFile(__DIR__ . '/../fixtures/test_expired_certificate.pem');
    expect($info->isExpired())->toBeTrue();
});

test('daysUntilExpiry is positive for valid cert', function () {
    $info = CertificateInspector::fromFile(__DIR__ . '/../fixtures/test_certificate.pem');
    expect($info->daysUntilExpiry())->toBeGreaterThan(0);
});

test('daysUntilExpiry is negative for expired cert', function () {
    $info = CertificateInspector::fromFile(__DIR__ . '/../fixtures/test_expired_certificate.pem');
    expect($info->daysUntilExpiry())->toBeLessThan(0);
});

test('isExpiringSoon respects warning threshold', function () {
    $info = CertificateInspector::fromFile(__DIR__ . '/../fixtures/test_certificate.pem');
    expect($info->isExpiringSoon(30))->toBeFalse();
    expect($info->isExpiringSoon(1000))->toBeTrue();
});

test('fromPem throws on invalid content', function () {
    CertificateInspector::fromPem('not a certificate');
})->throws(ConfigurationException::class);

test('fromFile throws on missing file', function () {
    CertificateInspector::fromFile('/nonexistent/cert.pem');
})->throws(ConfigurationException::class);
