<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayCheckout\Dto;

use Ux2Dev\Borica\InfopayCheckout\Enum\PaymentRequestStatusCode;
use Ux2Dev\Borica\InfopayCheckout\Enum\PaymentStatusCode;

final readonly class PaymentStatus
{
    public function __construct(
        public ?PaymentRequestStatusEntry $paymentRequestStatus,
        public ?PaymentStatusEntry $paymentStatus,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $status = $data['status'] ?? [];

        $prs = null;
        if (isset($status['PaymentRequestStatus'])) {
            $prs = new PaymentRequestStatusEntry(
                code: PaymentRequestStatusCode::from($status['PaymentRequestStatus']['Code']),
                isFinal: (bool) $status['PaymentRequestStatus']['IsFinal'],
            );
        }

        $ps = null;
        if (isset($status['PaymentStatus'])) {
            $ps = new PaymentStatusEntry(
                code: PaymentStatusCode::from($status['PaymentStatus']['Code']),
                isFinal: (bool) $status['PaymentStatus']['IsFinal'],
            );
        }

        return new self($prs, $ps);
    }
}

final readonly class PaymentRequestStatusEntry
{
    public function __construct(
        public PaymentRequestStatusCode $code,
        public bool $isFinal,
    ) {}
}

final readonly class PaymentStatusEntry
{
    public function __construct(
        public PaymentStatusCode $code,
        public bool $isFinal,
    ) {}
}
