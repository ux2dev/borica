<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Dto;

use Ux2Dev\Borica\InfopayErp\Enum\AccountType;

/**
 * Full account record returned from GET /api/accounts or /api/accounts/{id}.
 * Balances are only populated when ?withBalance=true was requested.
 */
final readonly class Account
{
    /** @param array<int, Balance> $balances */
    public function __construct(
        public string $accountId,
        public string $iban,
        public string $currency,
        public AccountType $type,
        public ?string $name = null,
        public ?string $bic = null,
        public ?string $ownerName = null,
        public ?LinksAccountDetails $links = null,
        public array $balances = [],
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $balances = [];
        foreach ((array) ($data['Balances'] ?? []) as $b) {
            $balances[] = Balance::fromArray((array) $b);
        }

        return new self(
            accountId: (string) ($data['AccountId'] ?? ''),
            iban: (string) ($data['IBAN'] ?? ''),
            currency: (string) ($data['Currency'] ?? ''),
            type: AccountType::from((string) ($data['Type'] ?? '')),
            name: isset($data['Name']) ? (string) $data['Name'] : null,
            bic: isset($data['BIC']) ? (string) $data['BIC'] : null,
            ownerName: isset($data['OwnerName']) ? (string) $data['OwnerName'] : null,
            links: isset($data['Links']) ? LinksAccountDetails::fromArray((array) $data['Links']) : null,
            balances: $balances,
        );
    }
}
