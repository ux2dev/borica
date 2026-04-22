<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

use Ux2Dev\Borica\InfopayErp\Enum\PaymentState;

/**
 * Response from GET /api/payments/{id}/status and bulk /status — wraps
 * the coarse state (New/Sent/Locked/Closed) plus the nested transaction
 * status object with the fine-grained status code and finality flag.
 */
final readonly class PaymentStatus
{
    public function __construct(
        public PaymentState $transactionState,
        public ?TransactionStatus $transactionStatus = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            transactionState: PaymentState::from((string) ($data['TransactionState'] ?? '')),
            transactionStatus: isset($data['TransactionStatus'])
                ? TransactionStatus::fromArray((array) $data['TransactionStatus'])
                : null,
        );
    }
}
