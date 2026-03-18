<?php
declare(strict_types=1);
namespace Ux2Dev\Borica\Signing;

use Ux2Dev\Borica\Enum\TransactionType;

class MacGeneral
{
    private const REQUEST_FIELDS = [
        'standard' => ['TERMINAL', 'TRTYPE', 'AMOUNT', 'CURRENCY', 'ORDER', 'TIMESTAMP', 'NONCE'],
        'status_check' => ['TERMINAL', 'TRTYPE', 'ORDER', 'NONCE'],
    ];

    private const RESPONSE_FIELDS = [
        'ACTION', 'RC', 'APPROVAL', 'TERMINAL', 'TRTYPE', 'AMOUNT', 'CURRENCY',
        'ORDER', 'RRN', 'INT_REF', 'PARES_STATUS', 'ECI', 'TIMESTAMP', 'NONCE',
    ];

    public function buildRequestSigningData(TransactionType $type, array $fields): string
    {
        $fieldNames = $type === TransactionType::StatusCheck
            ? self::REQUEST_FIELDS['status_check']
            : self::REQUEST_FIELDS['standard'];
        $data = $this->buildSigningString($fieldNames, $fields);
        if ($type !== TransactionType::StatusCheck) {
            $data .= '-';
        }
        return $data;
    }

    public function buildResponseSigningData(TransactionType $type, array $fields): string
    {
        $data = $this->buildSigningString(self::RESPONSE_FIELDS, $fields);
        $data .= '-';
        return $data;
    }

    private function buildSigningString(array $fieldNames, array $fields): string
    {
        $data = '';
        foreach ($fieldNames as $name) {
            $value = $fields[$name] ?? '';
            if ($value === '') {
                $data .= '-';
            } else {
                $data .= strlen($value) . $value;
            }
        }
        return $data;
    }
}
