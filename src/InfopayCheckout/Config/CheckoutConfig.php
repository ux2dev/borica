<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayCheckout\Config;

use Ux2Dev\Borica\Exception\ConfigurationException;

final class CheckoutConfig
{
    public readonly string $baseUrl;

    public function __construct(
        string $baseUrl,
        public readonly string $authId,
        public readonly string $authSecret,
        public readonly string $shopId,
        private readonly string $privateKey,
        private readonly string $certificate,
        private readonly ?string $privateKeyPassphrase = null,
    ) {
        if ($baseUrl === '') {
            throw new ConfigurationException('Checkout baseUrl must not be empty');
        }
        if (!str_starts_with($baseUrl, 'https://')) {
            throw new ConfigurationException('Checkout baseUrl must use https://');
        }
        $this->baseUrl = rtrim($baseUrl, '/');

        if (!str_contains($privateKey, '-----BEGIN')) {
            throw new ConfigurationException('Checkout privateKey does not look like a PEM-encoded key');
        }

        if (!str_starts_with($certificate, '-----BEGIN')) {
            throw new ConfigurationException('Checkout certificate does not look like a PEM-encoded certificate');
        }
        if (@openssl_x509_parse($certificate) === false) {
            throw new ConfigurationException('Checkout certificate is not a valid PEM-encoded X.509 certificate');
        }
    }

    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

    public function getCertificate(): string
    {
        return $this->certificate;
    }

    public function getPrivateKeyPassphrase(): ?string
    {
        return $this->privateKeyPassphrase;
    }
}
