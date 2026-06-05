<?php

declare(strict_types=1);

namespace Ux2Dev\Borica;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Ux2Dev\Borica\Cgi\CgiArea;
use Ux2Dev\Borica\Config\BoricaConfig;
use Ux2Dev\Borica\InfopayCheckout\CheckoutArea;
use Ux2Dev\Borica\InfopayErp\ErpArea;

/**
 * Framework-agnostic entry point. One instance per tenant; reach a backend via
 * the service accessors ($borica->cgi(), ->checkout(), ->erp()).
 */
final class Borica
{
    private ?CgiArea $cgi = null;
    private ?CheckoutArea $checkout = null;
    private ?ErpArea $erp = null;

    public function __construct(
        private readonly BoricaConfig $config,
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly ?LoggerInterface $logger = null,
        private readonly ?string $boricaPublicKey = null,
    ) {}

    public function cgi(): CgiArea
    {
        return $this->cgi ??= new CgiArea($this->config->requireCgi(), $this->logger, $this->boricaPublicKey);
    }

    public function checkout(): CheckoutArea
    {
        return $this->checkout ??= new CheckoutArea(
            $this->config->requireCheckout(),
            $this->httpClient,
            $this->requestFactory,
            $this->streamFactory,
            $this->logger,
        );
    }

    public function erp(): ErpArea
    {
        return $this->erp ??= new ErpArea(
            $this->config->requireErp(),
            $this->httpClient,
            $this->requestFactory,
            $this->streamFactory,
        );
    }
}
