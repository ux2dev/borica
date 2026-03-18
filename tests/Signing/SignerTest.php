<?php
declare(strict_types=1);

use Ux2Dev\Borica\Exception\SigningException;
use Ux2Dev\Borica\Signing\Signer;

beforeEach(function () {
    $this->signer = new Signer();
    $this->privateKey = file_get_contents(__DIR__ . '/../fixtures/test_private_key.pem');
    $this->publicKey = file_get_contents(__DIR__ . '/../fixtures/test_public_key.pem');
    $this->encryptedPrivateKey = file_get_contents(__DIR__ . '/../fixtures/test_private_key_encrypted.pem');
});

test('sign returns 512 uppercase hex chars', function () {
    $pSign = $this->signer->sign('test data', $this->privateKey);
    expect($pSign)->toHaveLength(512)->toMatch('/^[A-F0-9]{512}$/');
});

test('sign and verify round trip', function () {
    $data = '8V180000111149.003BGN6154744142020101212475732NONCE-';
    $pSign = $this->signer->sign($data, $this->privateKey);
    expect($this->signer->verify($data, $pSign, $this->publicKey))->toBeTrue();
});

test('verify returns false for tampered data', function () {
    $pSign = $this->signer->sign('original data', $this->privateKey);
    expect($this->signer->verify('tampered data', $pSign, $this->publicKey))->toBeFalse();
});

test('sign with passphrase protected key', function () {
    $pSign = $this->signer->sign('test data', $this->encryptedPrivateKey, 'testpass');
    expect($this->signer->verify('test data', $pSign, $this->publicKey))->toBeTrue();
});

test('sign throws on invalid key', function () {
    $this->signer->sign('data', 'not-a-valid-key');
})->throws(SigningException::class);

test('sign throws on wrong passphrase', function () {
    $this->signer->sign('data', $this->encryptedPrivateKey, 'wrongpassword');
})->throws(SigningException::class);

test('verify throws on invalid public key', function () {
    $this->signer->verify('data', str_repeat('A', 512), 'not-a-valid-key');
})->throws(SigningException::class);
