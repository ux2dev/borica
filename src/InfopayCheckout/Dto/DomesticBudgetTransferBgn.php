<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayCheckout\Dto;

use Ux2Dev\Borica\InfopayCheckout\Enum\PaymentType;
use Ux2Dev\Borica\InfopayCheckout\Enum\ServiceLevel;

final readonly class DomesticBudgetTransferBgn extends PaymentDetails
{
    public function __construct(
        public string $remittanceInformationUnstructured,
        public string $ultimateDebtor,
        public BudgetPaymentDetails $budgetPaymentDetails,
        public ?ServiceLevel $serviceLevel = null,
        public ?string $endToEndIdentification = null,
    ) {}

    public function type(): PaymentType
    {
        return PaymentType::DomesticBudgetTransfersBgn;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $out = [
            'type' => $this->type()->value,
            'remittanceInformationUnstructured' => $this->remittanceInformationUnstructured,
            'ultimateDebtor' => $this->ultimateDebtor,
            'budgetPaymentDetails' => $this->budgetPaymentDetails->toArray(),
        ];
        if ($this->serviceLevel !== null) {
            $out['serviceLevel'] = $this->serviceLevel->value;
        }
        if ($this->endToEndIdentification !== null) {
            $out['endToEndIdentification'] = $this->endToEndIdentification;
        }
        return $out;
    }
}
