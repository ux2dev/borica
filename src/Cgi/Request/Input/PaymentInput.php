<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Cgi\Request\Input;

use Ux2Dev\Borica\Contracts\Arrayable;

/**
 * Caller-supplied fields for initiating a CGI purchase or pre-authorization.
 * Config-derived fields (terminal, merchant, currency, signature) are added by
 * the resource.
 */
final readonly class PaymentInput implements Arrayable
{
    /** @param array<string, mixed> $mInfo */
    public function __construct(
        public string $amount,
        public string $order,
        public string $description,
        public array $mInfo = [],
        public string $adCustBorOrderId = '',
        public string $language = 'BG',
        public string $email = '',
        public string $merchantUrl = '',
        public ?string $timestamp = null,
        public ?string $nonce = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'order' => $this->order,
            'description' => $this->description,
            'mInfo' => $this->mInfo,
            'adCustBorOrderId' => $this->adCustBorOrderId,
            'language' => $this->language,
            'email' => $this->email,
            'merchantUrl' => $this->merchantUrl,
            'timestamp' => $this->timestamp,
            'nonce' => $this->nonce,
        ];
    }
}
