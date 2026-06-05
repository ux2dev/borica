<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Config;

use Ux2Dev\Borica\Enum\Environment;
use Ux2Dev\Borica\Exception\ConfigurationException;
use Ux2Dev\Borica\InfopayCheckout\Config\CheckoutConfig;
use Ux2Dev\Borica\InfopayErp\Config\ErpConfig;

/**
 * Aggregate config for one tenant. Each backend is optional — a tenant may
 * use only CGI, or all three. Accessing an unconfigured backend throws.
 */
final readonly class BoricaConfig
{
    public function __construct(
        public ?CgiConfig $cgi = null,
        public ?CheckoutConfig $checkout = null,
        public ?ErpConfig $erp = null,
        public Environment $environment = Environment::Production,
    ) {}

    public function requireCgi(): CgiConfig
    {
        return $this->cgi ?? throw new ConfigurationException('CGI is not configured for this tenant');
    }

    public function requireCheckout(): CheckoutConfig
    {
        return $this->checkout ?? throw new ConfigurationException('Checkout is not configured for this tenant');
    }

    public function requireErp(): ErpConfig
    {
        return $this->erp ?? throw new ConfigurationException('ERP is not configured for this tenant');
    }
}
