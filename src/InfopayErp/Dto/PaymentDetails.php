<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

use DateTimeImmutable;

/**
 * Invoice-scope payment details — which payment method, optional notes,
 * and optional due date. Not to be confused with the SEPA payment DTOs.
 */
final readonly class PaymentDetails
{
    public function __construct(
        public PaymentMethod $paymentMethod,
        public ?string $notes = null,
        public ?DateTimeImmutable $dueDate = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $out = ['paymentMethod' => $this->paymentMethod->toArray()];
        if ($this->notes !== null) {
            $out['notes'] = $this->notes;
        }
        if ($this->dueDate !== null) {
            $out['dueDate'] = $this->dueDate->format('Y-m-d');
        }
        return $out;
    }
}
