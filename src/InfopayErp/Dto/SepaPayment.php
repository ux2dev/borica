<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

use Ux2Dev\Borica\InfopayErp\Enum\SepaServiceLevel;

/**
 * Single SEPA payment line — used as an array item inside a bulk request
 * and as the sole payment inside a single-payment request.
 */
final readonly class SepaPayment
{
    public function __construct(
        public string $creditorName,
        public AccountReference $creditorAccount,
        public AddressReference $creditorAddress,
        public AmountRequest $instructedAmount,
        public string $remittanceInformationUnstructured,
        public ?string $endToEndIdentification = null,
        public ?SepaServiceLevel $serviceLevel = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $out = [
            'CreditorName' => $this->creditorName,
            'CreditorAccount' => $this->creditorAccount->toArray(),
            'CreditorAddress' => $this->creditorAddress->toArray(),
            'InstructedAmount' => $this->instructedAmount->toArray(),
            'RemittanceInformationUnstructured' => $this->remittanceInformationUnstructured,
        ];
        if ($this->endToEndIdentification !== null) {
            $out['EndToEndIdentification'] = $this->endToEndIdentification;
        }
        if ($this->serviceLevel !== null) {
            $out['ServiceLevel'] = $this->serviceLevel->value;
        }
        return $out;
    }
}
