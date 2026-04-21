<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayCheckout\Dto;

use DateTimeImmutable;
use Ux2Dev\Borica\InfopayCheckout\Enum\PaymentLanguage;

final readonly class PaymentRequestDto
{
    /**
     * @param array<int, Account> $beneficiaryAlternativeAccounts
     */
    public function __construct(
        public string $shopId,
        public Account $beneficiaryDefaultAccount,
        public InstructedAmount $instructedAmount,
        public string $details,
        public DateTimeImmutable $validTime,
        public string $externalReferenceId,
        public PaymentDetails $paymentDetails,
        public array $beneficiaryAlternativeAccounts = [],
        public ?string $successUrl = null,
        public ?string $errorUrl = null,
        public ?PaymentLanguage $language = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $out = [
            'shopId' => $this->shopId,
            'beneficiaryDefaultAccount' => $this->beneficiaryDefaultAccount->toArray(),
            'instructedAmount' => $this->instructedAmount->toArray(),
            'details' => $this->details,
            'validTime' => $this->validTime->format(\DateTimeInterface::ATOM),
            'externalReferenceId' => $this->externalReferenceId,
            'paymentDetails' => $this->paymentDetails->toArray(),
        ];

        if ($this->beneficiaryAlternativeAccounts !== []) {
            $out['beneficiaryAlternativeAccounts'] = array_map(
                fn (Account $a) => $a->toArray(),
                $this->beneficiaryAlternativeAccounts,
            );
        }
        if ($this->successUrl !== null) {
            $out['successURL'] = $this->successUrl;
        }
        if ($this->errorUrl !== null) {
            $out['errorURL'] = $this->errorUrl;
        }
        if ($this->language !== null) {
            $out['language'] = $this->language->value;
        }

        return $out;
    }
}
