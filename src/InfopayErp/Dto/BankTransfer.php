<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

use Ux2Dev\Borica\InfopayErp\Enum\PaymentMethodType;

final readonly class BankTransfer extends PaymentMethod
{
    /** @param array<int, BankTransferAccount> $accounts */
    public function __construct(
        public array $accounts,
        public ?string $paymentOrderDetails = null,
    ) {}

    public function type(): PaymentMethodType
    {
        return PaymentMethodType::BankTransfer;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $out = [
            'paymentType' => $this->type()->value,
            'accounts' => array_map(fn (BankTransferAccount $a) => $a->toArray(), $this->accounts),
        ];
        if ($this->paymentOrderDetails !== null) {
            $out['paymentOrderDetails'] = $this->paymentOrderDetails;
        }
        return $out;
    }
}
