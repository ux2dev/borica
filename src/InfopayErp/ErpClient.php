<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Ux2Dev\Borica\InfopayErp\Config\ErpConfig;
use Ux2Dev\Borica\InfopayErp\Http\HttpTransport;
use Ux2Dev\Borica\InfopayErp\Resource\AccountsResource;
use Ux2Dev\Borica\InfopayErp\Resource\BulkPaymentsResource;
use Ux2Dev\Borica\InfopayErp\Resource\InvoicesResource;
use Ux2Dev\Borica\InfopayErp\Resource\PaymentsResource;
use Ux2Dev\Borica\InfopayErp\Resource\SessionsResource;
use Ux2Dev\Borica\InfopayErp\Resource\SynchronizationsResource;
use Ux2Dev\Borica\InfopayErp\Resource\TransactionsResource;

/**
 * Facade over the Infopay ERP Integration API. Resources are lazily
 * instantiated on first access. Session management is explicit — create
 * one via sessions()->create() and pass it to every subsequent resource
 * call. Auto-refresh on 401 is intentionally left to integration-layer
 * code (e.g. Laravel middleware).
 */
final class ErpClient
{
    private readonly HttpTransport $transport;

    private ?SessionsResource $sessions = null;

    private ?SynchronizationsResource $synchronizations = null;

    private ?AccountsResource $accounts = null;

    private ?TransactionsResource $transactions = null;

    private ?BulkPaymentsResource $bulkPayments = null;

    private ?PaymentsResource $payments = null;

    private ?InvoicesResource $invoices = null;

    public function __construct(
        private readonly ErpConfig $config,
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
    ) {
        $this->transport = new HttpTransport($httpClient, $requestFactory, $streamFactory);
    }

    public function sessions(): SessionsResource
    {
        return $this->sessions ??= new SessionsResource($this->config, $this->transport);
    }

    public function synchronizations(): SynchronizationsResource
    {
        return $this->synchronizations ??= new SynchronizationsResource($this->config, $this->transport);
    }

    public function accounts(): AccountsResource
    {
        return $this->accounts ??= new AccountsResource($this->config, $this->transport);
    }

    public function transactions(): TransactionsResource
    {
        return $this->transactions ??= new TransactionsResource($this->config, $this->transport);
    }

    public function bulkPayments(): BulkPaymentsResource
    {
        return $this->bulkPayments ??= new BulkPaymentsResource($this->config, $this->transport);
    }

    public function payments(): PaymentsResource
    {
        return $this->payments ??= new PaymentsResource($this->config, $this->transport);
    }

    public function invoices(): InvoicesResource
    {
        return $this->invoices ??= new InvoicesResource($this->config, $this->transport);
    }
}
