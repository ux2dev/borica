<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Cgi;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Ux2Dev\Borica\Cgi\Resource\PaymentsResource;
use Ux2Dev\Borica\Cgi\Resource\PreAuthResource;
use Ux2Dev\Borica\Cgi\Resource\ResponsesResource;
use Ux2Dev\Borica\Cgi\Resource\StatusResource;
use Ux2Dev\Borica\Config\MerchantConfig;
use Ux2Dev\Borica\Signing\MacGeneral;
use Ux2Dev\Borica\Signing\Signer;

final class CgiClient
{
    private readonly MacGeneral $macGeneral;
    private readonly Signer $signer;
    private readonly LoggerInterface $logger;

    private ?PaymentsResource $payments = null;
    private ?PreAuthResource $preAuth = null;
    private ?StatusResource $status = null;
    private ?ResponsesResource $responses = null;

    public function __construct(
        private readonly MerchantConfig $config,
        ?LoggerInterface $logger = null,
        private readonly ?string $boricaPublicKey = null,
    ) {
        $this->macGeneral = new MacGeneral();
        $this->signer = new Signer();
        $this->logger = $logger ?? new NullLogger();
    }

    public function getGatewayUrl(): string
    {
        return $this->config->environment->value;
    }

    public function payments(): PaymentsResource
    {
        return $this->payments ??= new PaymentsResource(
            $this->config,
            $this->macGeneral,
            $this->signer,
            $this->logger,
        );
    }

    public function preAuth(): PreAuthResource
    {
        return $this->preAuth ??= new PreAuthResource(
            $this->config,
            $this->macGeneral,
            $this->signer,
            $this->logger,
        );
    }

    public function status(): StatusResource
    {
        return $this->status ??= new StatusResource(
            $this->config,
            $this->macGeneral,
            $this->signer,
            $this->logger,
        );
    }

    public function responses(): ResponsesResource
    {
        return $this->responses ??= new ResponsesResource(
            $this->config,
            $this->macGeneral,
            $this->signer,
            $this->logger,
            $this->boricaPublicKey,
        );
    }
}
