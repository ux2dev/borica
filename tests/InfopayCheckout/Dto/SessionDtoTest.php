<?php

declare(strict_types=1);

use Ux2Dev\Borica\InfopayCheckout\Dto\Session;
use Ux2Dev\Borica\InfopayCheckout\Enum\SessionCreateStatus;

test('Session holds sessionId, sessionKey, status', function () {
    $s = new Session(
        sessionId: 'abc-123',
        sessionKey: 'secret-key',
        status: SessionCreateStatus::Success,
    );
    expect($s->sessionId)->toBe('abc-123');
    expect($s->sessionKey)->toBe('secret-key');
    expect($s->status)->toBe(SessionCreateStatus::Success);
});

test('Session fromArray parses successful response payload', function () {
    $s = Session::fromArray([
        'sessionId' => 'abc-123',
        'sessionKey' => 'secret',
        'status' => 'Success',
    ]);

    expect($s->sessionId)->toBe('abc-123');
    expect($s->status)->toBe(SessionCreateStatus::Success);
});

test('Session fromArray parses failure status without session IDs', function () {
    $s = Session::fromArray(['status' => 'InvalidCredentials']);

    expect($s->sessionId)->toBe('');
    expect($s->sessionKey)->toBe('');
    expect($s->status)->toBe(SessionCreateStatus::InvalidCredentials);
});

test('Session provides HTTP basic auth header value', function () {
    $s = new Session('id1', 'key1', SessionCreateStatus::Success);
    expect($s->basicAuthHeader())->toBe('Basic ' . base64_encode('id1:key1'));
});
