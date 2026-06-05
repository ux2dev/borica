<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayCheckout;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Ux2Dev\Borica\Http\ApiTransport;
use Ux2Dev\Borica\InfopayCheckout\Config\CheckoutConfig;
use Ux2Dev\Borica\InfopayCheckout\Http\JwsSigner;
use Ux2Dev\Borica\InfopayCheckout\Resource\PaymentRequestsResource;
use Ux2Dev\Borica\InfopayCheckout\Resource\SessionsResource;

/** Infopay Checkout service area. */
final class CheckoutArea
{
    private readonly ApiTransport $transport;
    private readonly JwsSigner $jwsSigner;
    private readonly LoggerInterface $logger;

    private ?SessionsResource $sessions = null;
    private ?PaymentRequestsResource $paymentRequests = null;

    public function __construct(
        private readonly CheckoutConfig $config,
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        ?LoggerInterface $logger = null,
    ) {
        $this->transport = new ApiTransport($httpClient, $requestFactory, $streamFactory);
        $this->jwsSigner = new JwsSigner();
        $this->logger = $logger ?? new NullLogger();
    }

    public function sessions(): SessionsResource
    {
        return $this->sessions ??= new SessionsResource($this->config, $this->transport);
    }

    public function paymentRequests(): PaymentRequestsResource
    {
        return $this->paymentRequests ??= new PaymentRequestsResource($this->config, $this->transport, $this->jwsSigner);
    }
}
