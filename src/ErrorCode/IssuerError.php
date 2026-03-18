<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\ErrorCode;

final class IssuerError
{
    private const CODES = [
        '00' => 'Successfully completed',
        '01' => 'Refer to card issuer',
        '04' => 'Pick up card',
        '05' => 'Do not honour',
        '06' => 'Error',
        '12' => 'Invalid transaction',
        '13' => 'Invalid amount',
        '14' => 'No such card',
        '15' => 'No such issuer',
        '17' => 'Customer cancellation',
        '30' => 'Format error',
        '35' => 'Pick-up, contact acquirer',
        '36' => 'Pick up, card restricted',
        '37' => 'Pick up, call acquirer security',
        '38' => 'Pick up, PIN tries exceeded',
        '39' => 'No credit account',
        '40' => 'Requested function not supported',
        '41' => 'Pick up, lost card',
        '42' => 'No universal account',
        '43' => 'Pick up, stolen card',
        '54' => 'Expired card',
        '55' => 'Incorrect PIN',
        '56' => 'No card record',
        '57' => 'Transaction not permitted to cardholder',
        '58' => 'Transaction not permitted to terminal',
        '59' => 'Suspected fraud',
        '85' => 'No reason to decline',
        '88' => 'Cryptographic failure',
        '89' => 'Authentication failure',
        '91' => 'Issuer or switch inoperative',
        '95' => 'Reconcile error / Auth not found',
        '96' => 'System malfunction',
    ];

    public static function getMessage(string $code): string
    {
        return self::CODES[$code] ?? 'Unknown issuer error';
    }
}
