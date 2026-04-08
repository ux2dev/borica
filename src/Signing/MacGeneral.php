<?php
declare(strict_types=1);
namespace Ux2Dev\Borica\Signing;

use Ux2Dev\Borica\Enum\SigningSchema;
use Ux2Dev\Borica\Enum\TransactionType;

class MacGeneral
{
    private const REQUEST_FIELDS = [
        'mac_general' => ['TERMINAL', 'TRTYPE', 'AMOUNT', 'CURRENCY', 'ORDER', 'TIMESTAMP', 'NONCE'],
        'mac_extended' => ['TERMINAL', 'TRTYPE', 'AMOUNT', 'CURRENCY', 'ORDER', 'MERCHANT', 'TIMESTAMP', 'NONCE'],
        'mac_advanced' => ['TERMINAL', 'TRTYPE', 'AMOUNT', 'CURRENCY', 'ORDER', 'TIMESTAMP', 'NONCE'],
        'status_check' => ['TERMINAL', 'TRTYPE', 'ORDER', 'NONCE'],
    ];

    private const RESPONSE_FIELDS = [
        'ACTION', 'RC', 'APPROVAL', 'TERMINAL', 'TRTYPE', 'AMOUNT', 'CURRENCY',
        'ORDER', 'RRN', 'INT_REF', 'PARES_STATUS', 'ECI', 'TIMESTAMP', 'NONCE',
    ];

    public function buildRequestSigningData(
        TransactionType $type,
        array $fields,
        SigningSchema $schema = SigningSchema::MacGeneral,
    ): string {
        if ($type === TransactionType::StatusCheck) {
            return $this->buildSigningString(self::REQUEST_FIELDS['status_check'], $fields);
        }

        $fieldNames = match ($schema) {
            SigningSchema::MacGeneral => self::REQUEST_FIELDS['mac_general'],
            SigningSchema::MacExtended => self::REQUEST_FIELDS['mac_extended'],
            SigningSchema::MacAdvanced => self::REQUEST_FIELDS['mac_advanced'],
        };

        $data = $this->buildSigningString($fieldNames, $fields);

        // Only MAC_GENERAL appends the trailing RFU dash
        if ($schema === SigningSchema::MacGeneral) {
            $data .= '-';
        }

        return $data;
    }

    public function buildResponseSigningData(array $fields): string
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
                $data .= mb_strlen($value, '8bit') . $value;
            }
        }
        return $data;
    }
}
