<?php

declare(strict_types=1);

use Ux2Dev\Borica\ErrorCode\GatewayError;

test('known code -17 returns description containing denied', function () {
    $message = GatewayError::getMessage('-17');
    expect($message)->toContain('denied');
});

test('unknown code -99 returns default message', function () {
    $message = GatewayError::getMessage('-99');
    expect($message)->toBe('Unknown gateway error');
});

test('all 30 defined codes have non-default messages', function () {
    $codes = [
        '-1', '-2', '-3', '-4', '-5', '-6', '-7',
        '-10', '-11', '-12', '-13', '-15', '-16', '-17',
        '-19', '-20', '-21', '-22', '-23', '-24', '-25',
        '-26', '-27', '-28', '-29', '-30', '-31', '-32',
        '-33', '-40',
    ];

    foreach ($codes as $code) {
        expect(GatewayError::getMessage($code))->not->toBe('Unknown gateway error');
    }
});
