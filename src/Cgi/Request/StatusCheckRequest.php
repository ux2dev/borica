<?php
declare(strict_types=1);

namespace Ux2Dev\Borica\Cgi\Request;

use Ux2Dev\Borica\Enum\TransactionType;

final readonly class StatusCheckRequest implements RequestInterface
{
    public function __construct(
        private string $terminal,
        private string $order,
        private string $nonce,
        private string $pSign,
        private string $tranTrtype,
    ) {}

    public function getTransactionType(): TransactionType
    {
        return TransactionType::StatusCheck;
    }

    public function getSigningFields(): array
    {
        return [
            'TERMINAL' => $this->terminal,
            'TRTYPE' => (string) TransactionType::StatusCheck->value,
            'ORDER' => $this->order,
            'NONCE' => $this->nonce,
        ];
    }

    public function toArray(): array
    {
        return [
            'TERMINAL' => $this->terminal,
            'TRTYPE' => (string) TransactionType::StatusCheck->value,
            'ORDER' => $this->order,
            'TRAN_TRTYPE' => $this->tranTrtype,
            'NONCE' => $this->nonce,
            'P_SIGN' => $this->pSign,
        ];
    }
}
