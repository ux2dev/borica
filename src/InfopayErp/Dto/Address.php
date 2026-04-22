<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

/**
 * Full postal address used for Invoice customers (distinct from
 * `AddressReference` used in SEPA payments, which has different fields).
 */
final readonly class Address
{
    public function __construct(
        public string $country,
        public string $city,
        public ?string $address = null,
    ) {}

    /** @return array<string, string> */
    public function toArray(): array
    {
        $out = [
            'country' => $this->country,
            'city' => $this->city,
        ];
        if ($this->address !== null) {
            $out['address'] = $this->address;
        }
        return $out;
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            country: (string) ($data['country'] ?? ''),
            city: (string) ($data['city'] ?? ''),
            address: isset($data['address']) ? (string) $data['address'] : null,
        );
    }
}
