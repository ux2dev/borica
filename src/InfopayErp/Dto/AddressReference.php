<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

final readonly class AddressReference
{
    public function __construct(
        public string $country,
        public ?string $city = null,
        public ?string $street = null,
        public ?string $postalCode = null,
    ) {}

    /** @return array<string, string> */
    public function toArray(): array
    {
        $out = ['Country' => $this->country];
        if ($this->city !== null) {
            $out['City'] = $this->city;
        }
        if ($this->street !== null) {
            $out['Street'] = $this->street;
        }
        if ($this->postalCode !== null) {
            $out['PostalCode'] = $this->postalCode;
        }
        return $out;
    }
}
