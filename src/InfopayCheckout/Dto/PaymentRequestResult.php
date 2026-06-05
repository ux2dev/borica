<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayCheckout\Dto;

use Ux2Dev\Borica\Contracts\BoricaResult;

final readonly class PaymentRequestResult implements BoricaResult
{
    public function __construct(
        public string $paymentRequestId,
        public string $checkoutUrl,
        public string $requestStatusUrl,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        $links = $data['_links'] ?? [];
        return new self(
            paymentRequestId: (string) ($data['paymentRequestId'] ?? ''),
            checkoutUrl: (string) ($links['checkoutURL']['href'] ?? ''),
            requestStatusUrl: (string) ($links['requestStatusURL']['href'] ?? ''),
        );
    }
}
