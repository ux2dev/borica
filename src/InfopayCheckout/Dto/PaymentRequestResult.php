<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayCheckout\Dto;

final readonly class PaymentRequestResult
{
    public function __construct(
        public string $paymentRequestId,
        public string $checkoutUrl,
        public string $requestStatusUrl,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $links = $data['_links'] ?? [];
        return new self(
            paymentRequestId: (string) ($data['paymentRequestId'] ?? ''),
            checkoutUrl: (string) ($links['checkoutURL']['href'] ?? ''),
            requestStatusUrl: (string) ($links['requestStatusURL']['href'] ?? ''),
        );
    }
}
