<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

use DateTimeImmutable;
use InvalidArgumentException;
use Ux2Dev\Borica\InfopayErp\Enum\Currency;
use Ux2Dev\Borica\InfopayErp\Enum\Language;

/**
 * Request body for POST /api/invoices. Most fields are required per the
 * spec; `additionalInformation` is the only optional top-level field.
 */
final readonly class InvoiceCreateRequest
{
    public function __construct(
        public string $number,
        public DateTimeImmutable $taxEventDate,
        public DateTimeImmutable $invoiceDate,
        public Language $language,
        public Customer $customer,
        public Currency $currency,
        public Content $content,
        public PaymentDetails $paymentDetails,
        public string $numberSeriesId,
        public ?AdditionalInformation $additionalInformation = null,
    ) {
        if (! preg_match('/^[0-9]{10}$/', $number)) {
            throw new InvalidArgumentException('Invoice number must be exactly 10 digits');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $out = [
            'number' => $this->number,
            'taxEventDate' => $this->taxEventDate->format('Y-m-d'),
            'invoiceDate' => $this->invoiceDate->format('Y-m-d'),
            'language' => $this->language->value,
            'customer' => $this->customer->toArray(),
            'currency' => $this->currency->value,
            'content' => $this->content->toArray(),
            'paymentDetails' => $this->paymentDetails->toArray(),
            'numberSeriesId' => $this->numberSeriesId,
        ];
        if ($this->additionalInformation !== null) {
            $out['additionalInformation'] = $this->additionalInformation->toArray();
        }
        return $out;
    }
}
