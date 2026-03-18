<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\ErrorCode;

final class GatewayError
{
    private const CODES = [
        '-1' => 'Mandatory request field missing',
        '-2' => 'CGI request validation failed',
        '-3' => 'Acquirer host does not respond or wrong format',
        '-4' => 'No connection to acquirer host',
        '-5' => 'Acquirer host connection failed during processing',
        '-6' => 'e-Gateway configuration error',
        '-7' => 'Acquirer host response is invalid',
        '-10' => 'Error in Amount field',
        '-11' => 'Error in Currency field',
        '-12' => 'Error in Merchant ID field',
        '-13' => 'Referrer IP address mismatch',
        '-15' => 'Error in RRN field',
        '-16' => 'Another transaction in progress on terminal',
        '-17' => 'Terminal denied access',
        '-19' => 'Authentication request error or authentication failed',
        '-20' => 'Timestamp exceeded permitted difference',
        '-21' => 'Transaction already executed',
        '-22' => 'Invalid authentication information',
        '-23' => 'Invalid transaction context',
        '-24' => 'Transaction context data mismatch',
        '-25' => 'Transaction confirmation cancelled by user',
        '-26' => 'Invalid action BIN',
        '-27' => 'Invalid merchant name',
        '-28' => 'Invalid addendum field',
        '-29' => 'Invalid/duplicate authentication reference',
        '-30' => 'Declined as fraud',
        '-31' => 'Transaction already in progress',
        '-32' => 'Duplicate declined transaction',
        '-33' => 'Customer authentication in progress',
        '-40' => 'Client side transaction form in progress',
    ];

    public static function getMessage(string $code): string
    {
        return self::CODES[$code] ?? 'Unknown gateway error';
    }
}
