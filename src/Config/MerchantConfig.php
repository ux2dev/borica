<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Config;

use Ux2Dev\Borica\Enum\Currency;
use Ux2Dev\Borica\Enum\Environment;
use Ux2Dev\Borica\Exception\ConfigurationException;

final readonly class MerchantConfig
{
    public function __construct(
        public string $terminal,
        public string $merchantId,
        public string $merchantName,
        public string $privateKey,
        public Environment $environment,
        public Currency $currency,
        public string $country = 'BG',
        public string $timezoneOffset = '+03',
        public ?string $privateKeyPassphrase = null,
    ) {
        if ($terminal === '') {
            throw new ConfigurationException('terminal must not be empty');
        }
        if ($merchantId === '') {
            throw new ConfigurationException('merchantId must not be empty');
        }
        if ($merchantName === '') {
            throw new ConfigurationException('merchantName must not be empty');
        }
        if ($privateKey === '') {
            throw new ConfigurationException('privateKey must not be empty');
        }
    }
}
