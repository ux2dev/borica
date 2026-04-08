<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Config;

use Ux2Dev\Borica\Enum\Currency;
use Ux2Dev\Borica\Enum\Environment;
use Ux2Dev\Borica\Enum\SigningSchema;
use Ux2Dev\Borica\Exception\ConfigurationException;

final readonly class MerchantConfig
{
    public function __construct(
        public string $terminal,
        public string $merchantId,
        public string $merchantName,
        private string $privateKey,
        public Environment $environment,
        public Currency $currency,
        public SigningSchema $signingSchema = SigningSchema::MacGeneral,
        public string $country = 'BG',
        public string $timezoneOffset = '+03',
        private ?string $privateKeyPassphrase = null,
    ) {
        if (!preg_match('/^[A-Za-z0-9]{8}$/', $terminal)) {
            throw new ConfigurationException('terminal must be exactly 8 alphanumeric characters');
        }
        if (!preg_match('/^[A-Za-z0-9]{10}$/', $merchantId)) {
            throw new ConfigurationException('merchantId must be exactly 10 alphanumeric characters');
        }
        if ($merchantName === '') {
            throw new ConfigurationException('merchantName must not be empty');
        }
        if ($privateKey === '') {
            throw new ConfigurationException('privateKey must not be empty');
        }
        $testKey = openssl_pkey_get_private($privateKey, $privateKeyPassphrase ?? '');
        if ($testKey === false) {
            throw new ConfigurationException(
                'privateKey is not a valid PEM private key (or passphrase is wrong)'
            );
        }
    }

    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

    public function getPrivateKeyPassphrase(): ?string
    {
        return $this->privateKeyPassphrase;
    }

    public function __debugInfo(): array
    {
        return [
            'terminal' => $this->terminal,
            'merchantId' => $this->merchantId,
            'merchantName' => $this->merchantName,
            'privateKey' => '[REDACTED]',
            'environment' => $this->environment,
            'currency' => $this->currency,
            'signingSchema' => $this->signingSchema,
            'country' => $this->country,
            'timezoneOffset' => $this->timezoneOffset,
            'privateKeyPassphrase' => $this->privateKeyPassphrase !== null ? '[REDACTED]' : null,
        ];
    }

    public function __serialize(): array
    {
        throw new \LogicException(
            'MerchantConfig must not be serialized as it contains private key material'
        );
    }

    public function __unserialize(array $data): void
    {
        throw new \LogicException(
            'MerchantConfig must not be unserialized'
        );
    }
}
