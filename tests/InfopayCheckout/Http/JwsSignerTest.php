<?php

declare(strict_types=1);

use Ux2Dev\Borica\Exception\SignatureException;
use Ux2Dev\Borica\InfopayCheckout\Http\JwsSigner;

beforeEach(function () {
    $this->privateKey = file_get_contents(__DIR__ . '/../../fixtures/test_private_key.pem');
    $this->publicKey = file_get_contents(__DIR__ . '/../../fixtures/test_public_key.pem');
    $this->certificate = file_get_contents(__DIR__ . '/../../fixtures/test_certificate.pem');
});

test('signs a JSON body and produces a detached JWS with three segments', function () {
    $signer = new JwsSigner();

    $body = '{"foo":"bar"}';
    $jws = $signer->sign($body, $this->privateKey, $this->certificate);

    $parts = explode('.', $jws);
    expect($parts)->toHaveCount(3);
    expect($parts[1])->toBe(''); // detached: payload segment is empty
});

test('signed header contains x5c first then alg RS256 only', function () {
    $signer = new JwsSigner();

    $jws = $signer->sign('{"foo":"bar"}', $this->privateKey, $this->certificate);
    [$headerB64] = explode('.', $jws);

    $headerJson = base64UrlDecode($headerB64);
    $header = json_decode($headerJson, true);

    expect($header['alg'])->toBe('RS256');
    expect(array_key_exists('b64', $header))->toBeFalse();
    expect(array_key_exists('crit', $header))->toBeFalse();
    expect(array_key_exists('x5c', $header))->toBeTrue();
    expect($header['x5c'])->toBeArray()->toHaveCount(1);

    // x5c must come before alg in the JSON
    $keys = array_keys($header);
    expect($keys[0])->toBe('x5c');
    expect($keys[1])->toBe('alg');
});

test('x5c value is standard base64 without line breaks or PEM headers', function () {
    $signer = new JwsSigner();

    $jws = $signer->sign('{"foo":"bar"}', $this->privateKey, $this->certificate);
    [$headerB64] = explode('.', $jws);

    $header = json_decode(base64UrlDecode($headerB64), true);
    $x5c = $header['x5c'][0];

    // Must not contain PEM markers or whitespace
    expect($x5c)->not->toContain('-----');
    expect($x5c)->not->toContain("\n");
    expect($x5c)->not->toContain("\r");
    expect($x5c)->not->toContain(' ');

    // Must be valid standard base64 (not base64url — no - or _)
    expect(base64_decode($x5c, true))->not->toBeFalse();
});

test('signature verifies using standard JWS signing input', function () {
    $signer = new JwsSigner();

    $body = '{"foo":"bar"}';
    $jws = $signer->sign($body, $this->privateKey, $this->certificate);
    [$headerB64, , $sigB64] = explode('.', $jws);

    // Standard JWS signing input: base64url(header) + "." + base64url(body)
    $bodyB64 = base64UrlEncode($body);
    $signingInput = $headerB64 . '.' . $bodyB64;
    $sig = base64UrlDecode($sigB64);

    $verified = openssl_verify($signingInput, $sig, $this->publicKey, OPENSSL_ALGO_SHA256);
    expect($verified)->toBe(1);
});

test('throws SignatureException when private key is invalid', function () {
    $signer = new JwsSigner();
    $signer->sign('{}', 'not-a-real-pem', $this->certificate);
})->throws(SignatureException::class);

// Helpers for tests
function base64UrlDecode(string $s): string
{
    $s = strtr($s, '-_', '+/');
    $pad = strlen($s) % 4;
    if ($pad > 0) { $s .= str_repeat('=', 4 - $pad); }
    return base64_decode($s);
}

function base64UrlEncode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
