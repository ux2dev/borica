<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayCheckout\Dto;

use Ux2Dev\Borica\InfopayCheckout\Enum\PaymentType;
use Ux2Dev\Borica\InfopayCheckout\Enum\SepaServiceLevel;

final readonly class SepaCreditTransfer extends PaymentDetails
{
    public function __construct(
        public string $remittanceInformationUnstructured,
        public ?SepaServiceLevel $serviceLevel = null,
        public ?string $endToEndIdentification = null,
    ) {}

    public function type(): PaymentType
    {
        return PaymentType::SepaCreditTransfers;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $out = [
            'type' => $this->type()->value,
            'remittanceInformationUnstructured' => $this->remittanceInformationUnstructured,
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
