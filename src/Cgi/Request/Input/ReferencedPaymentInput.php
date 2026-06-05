<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Cgi\Request\Input;

use Ux2Dev\Borica\Contracts\Arrayable;

/**
 * Caller-supplied fields for an operation that references a prior transaction
 * by its RRN + INT_REF: reversal, pre-auth completion, pre-auth reversal.
 */
final readonly class ReferencedPaymentInput implements Arrayable
{
    public function __construct(
        public string $amount,
        public string $order,
        public string $rrn,
        public string $intRef,
        public string $description,
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
            'rrn' => $this->rrn,
            'intRef' => $this->intRef,
            'description' => $this->description,
            'adCustBorOrderId' => $this->adCustBorOrderId,
            'language' => $this->language,
            'email' => $this->email,
            'merchantUrl' => $this->merchantUrl,
            'timestamp' => $this->timestamp,
            'nonce' => $this->nonce,
        ];
    }
}
