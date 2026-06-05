<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

use Ux2Dev\Borica\Contracts\BoricaResult;

/**
 * Response from a payment creation endpoint. TransactionStatus here is a
 * free-form string label (distinct from the TransactionStatus DTO returned
 * by the /status endpoint).
 */
final readonly class PaymentResult implements BoricaResult
{
    public function __construct(
        public string $paymentId,
        public string $transactionStatus,
        public string $referencePaymentId,
        public ?PaymentLinks $links = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            paymentId: (string) ($data['PaymentId'] ?? ''),
            transactionStatus: (string) ($data['TransactionStatus'] ?? ''),
            referencePaymentId: (string) ($data['ReferencePaymentId'] ?? ''),
            links: isset($data['Links']) ? PaymentLinks::fromArray((array) $data['Links']) : null,
        );
    }
}
