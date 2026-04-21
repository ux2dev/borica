<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayCheckout\Dto;

use Ux2Dev\Borica\InfopayCheckout\Enum\TaxPayerType;

final readonly class BudgetPaymentDetails
{
    public function __construct(
        public string $taxPayerId,
        public TaxPayerType $taxPayerType,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'taxPayerId' => $this->taxPayerId,
            'taxPayerType' => $this->taxPayerType->value,
        ];
    }
}
