<?php

declare(strict_types=1);

use Ux2Dev\Borica\ErrorCode\IssuerError;

test('success code 00 returns description containing success', function () {
    $message = IssuerError::getMessage('00');
    expect(strtolower($message))->toContain('success');
});

test('known error code 05 returns non-empty message', function () {
    $message = IssuerError::getMessage('05');
    expect($message)->not->toBeEmpty();
});

test('unknown code returns default message', function () {
    $message = IssuerError::getMessage('99');
    expect($message)->toBe('Unknown issuer error');
});
