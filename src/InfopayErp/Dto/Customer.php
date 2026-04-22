<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

final readonly class Customer
{
    public function __construct(
        public string $identificationNumber,
        public string $name,
        public Address $address,
        public ?string $vatId = null,
        public ?string $email = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $out = [
            'identificationNumber' => $this->identificationNumber,
            'name' => $this->name,
            'address' => $this->address->toArray(),
        ];
        if ($this->vatId !== null) {
            $out['vatId'] = $this->vatId;
        }
        if ($this->email !== null) {
            $out['email'] = $this->email;
        }
        return $out;
    }
}
