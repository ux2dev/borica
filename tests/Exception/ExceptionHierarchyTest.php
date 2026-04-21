<?php

declare(strict_types=1);

use Ux2Dev\Borica\Exception\ApiException;
use Ux2Dev\Borica\Exception\AuthenticationException;
use Ux2Dev\Borica\Exception\BoricaException;
use Ux2Dev\Borica\Exception\SignatureException;
use Ux2Dev\Borica\Exception\TransportException;

test('TransportException extends BoricaException', function () {
    expect(new TransportException('x'))->toBeInstanceOf(BoricaException::class);
});

test('ApiException extends BoricaException', function () {
    expect(new ApiException('x', httpStatus: 500))->toBeInstanceOf(BoricaException::class);
});

test('AuthenticationException extends ApiException', function () {
    expect(new AuthenticationException('x', httpStatus: 401))->toBeInstanceOf(ApiException::class);
});

test('SignatureException extends BoricaException', function () {
    expect(new SignatureException('x'))->toBeInstanceOf(BoricaException::class);
});

test('ApiException carries HTTP status and body', function () {
    $e = new ApiException('bad request', httpStatus: 400, body: ['error' => 'invalid']);
    expect($e->getHttpStatus())->toBe(400);
    expect($e->getBody())->toBe(['error' => 'invalid']);
});
