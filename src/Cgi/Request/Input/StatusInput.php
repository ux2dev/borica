<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Cgi\Request\Input;

use Ux2Dev\Borica\Contracts\Arrayable;
use Ux2Dev\Borica\Enum\TransactionType;

/** Caller-supplied fields for a CGI transaction status check. */
final readonly class StatusInput implements Arrayable
{
    public function __construct(
        public string $order,
        public TransactionType $transactionType,
        public ?string $nonce = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'order' => $this->order,
            'transactionType' => $this->transactionType->value,
            'nonce' => $this->nonce,
        ];
    }
}
