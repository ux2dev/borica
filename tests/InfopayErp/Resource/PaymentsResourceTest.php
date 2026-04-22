<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\HttpFactory;
use Ux2Dev\Borica\InfopayErp\Config\ErpConfig;
use Ux2Dev\Borica\InfopayErp\Dto\AccountReference;
use Ux2Dev\Borica\InfopayErp\Dto\AddressReference;
use Ux2Dev\Borica\InfopayErp\Dto\AmountRequest;
use Ux2Dev\Borica\InfopayErp\Dto\BulkSepaPaymentRequest;
use Ux2Dev\Borica\InfopayErp\Dto\SepaPayment;
use Ux2Dev\Borica\InfopayErp\Dto\Session;
use Ux2Dev\Borica\InfopayErp\Dto\SingleSepaPaymentRequest;
use Ux2Dev\Borica\InfopayErp\Enum\Currency;
use Ux2Dev\Borica\InfopayErp\Enum\PaymentState;
use Ux2Dev\Borica\InfopayErp\Enum\PaymentStatusCode;
use Ux2Dev\Borica\InfopayErp\Enum\SepaServiceLevel;
use Ux2Dev\Borica\InfopayErp\Enum\SessionCreateStatus;
use Ux2Dev\Borica\InfopayErp\Http\HttpTransport;
use Ux2Dev\Borica\InfopayErp\Resource\BulkPaymentsResource;
use Ux2Dev\Borica\InfopayErp\Resource\PaymentsResource;
use Ux2Dev\Borica\Tests\InfopayErp\FakeHttpClient;

require_once __DIR__ . '/../Helpers.php';

beforeEach(function () {
    $this->config = new ErpConfig(
        baseUrl: 'https://integration.infopay.bg',
        uniqueId: 'unique-id',
        accessToken: 'access-token',
    );
    $this->factory = new HttpFactory();
    $this->session = new Session('sess', SessionCreateStatus::Success, 'key');
});

function buildSepa(): SepaPayment
{
    return new SepaPayment(
        creditorName: 'Acme Co',
        creditorAccount: new AccountReference('DE89370400440532013000'),
        creditorAddress: new AddressReference(country: 'DE', city: 'Berlin'),
        instructedAmount: new AmountRequest('100.00', Currency::Eur),
        remittanceInformationUnstructured: 'invoice 42',
        serviceLevel: SepaServiceLevel::Inst,
    );
}

test('single SEPA payment POSTs DebitorAccount + Payment shape', function () {
    $client = new FakeHttpClient([
        FakeHttpClient::json(201, [
            'PaymentId' => 'p-1',
            'TransactionStatus' => 'Accepted',
            'ReferencePaymentId' => 'ref-1',
            'Links' => ['ScaRedirect' => 'https://3ds.example/x', 'Status' => '/api/payments/p-1/status'],
        ]),
    ]);
    $resource = new PaymentsResource($this->config, new HttpTransport($client, $this->factory, $this->factory));

    $result = $resource->createSepa($this->session, new SingleSepaPaymentRequest(
        debtorAccount: new AccountReference('BG80BNBG96611020345678'),
        payment: buildSepa(),
    ));

    expect($result->paymentId)->toBe('p-1');
    expect($result->links?->scaRedirect)->toBe('https://3ds.example/x');

    $body = json_decode((string) $client->captured[0]->getBody(), true);
    expect($body)->toHaveKey('DebitorAccount');
    expect($body['DebitorAccount']['IBAN'])->toBe('BG80BNBG96611020345678');
    expect($body['Payment']['ServiceLevel'])->toBe('INST');
    expect((string) $client->captured[0]->getUri())->toBe('https://integration.infopay.bg/api/payments/sepa-credit-transfers');
});

test('bulk SEPA payment requires 2..250 payments and serializes the batch', function () {
    expect(fn () => new BulkSepaPaymentRequest(
        debtorAccount: new AccountReference('BG1'),
        payments: [buildSepa()],
    ))->toThrow(InvalidArgumentException::class);

    $client = new FakeHttpClient([
        FakeHttpClient::json(201, [
            'PaymentId' => 'b-1', 'TransactionStatus' => 'Accepted', 'ReferencePaymentId' => 'r-1',
        ]),
    ]);
    $resource = new BulkPaymentsResource($this->config, new HttpTransport($client, $this->factory, $this->factory));

    $resource->createSepa($this->session, new BulkSepaPaymentRequest(
        debtorAccount: new AccountReference('BG1'),
        payments: [buildSepa(), buildSepa()],
    ));

    $body = json_decode((string) $client->captured[0]->getBody(), true);
    expect($body['Payments'])->toHaveCount(2);
    expect((string) $client->captured[0]->getUri())->toBe('https://integration.infopay.bg/api/bulk-payments/sepa-credit-transfers');
});

test('getStatus parses TransactionState and TransactionStatus', function () {
    $client = new FakeHttpClient([
        FakeHttpClient::json(200, [
            'TransactionState' => 'Closed',
            'TransactionStatus' => ['Status' => 'Processed', 'IsFinal' => true],
        ]),
    ]);
    $resource = new PaymentsResource($this->config, new HttpTransport($client, $this->factory, $this->factory));

    $status = $resource->getStatus($this->session, 'p-1');

    expect($status->transactionState)->toBe(PaymentState::Closed);
    expect($status->transactionStatus?->status)->toBe(PaymentStatusCode::Processed);
    expect($status->transactionStatus?->isFinal)->toBeTrue();
});
