<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\HttpFactory;
use Ux2Dev\Borica\InfopayErp\Config\ErpConfig;
use Ux2Dev\Borica\InfopayErp\Dto\Address;
use Ux2Dev\Borica\InfopayErp\Dto\AmountPayable;
use Ux2Dev\Borica\InfopayErp\Dto\AmountsByVatGroup;
use Ux2Dev\Borica\InfopayErp\Dto\BankTransfer;
use Ux2Dev\Borica\InfopayErp\Dto\BankTransferAccount;
use Ux2Dev\Borica\InfopayErp\Dto\ContentWithVat;
use Ux2Dev\Borica\InfopayErp\Dto\Customer;
use Ux2Dev\Borica\InfopayErp\Dto\InvoiceCreateRequest;
use Ux2Dev\Borica\InfopayErp\Dto\ItemWithVat;
use Ux2Dev\Borica\InfopayErp\Dto\NonZeroVat;
use Ux2Dev\Borica\InfopayErp\Dto\PaymentDetails;
use Ux2Dev\Borica\InfopayErp\Dto\Session;
use Ux2Dev\Borica\InfopayErp\Enum\Currency;
use Ux2Dev\Borica\InfopayErp\Enum\Language;
use Ux2Dev\Borica\InfopayErp\Enum\SessionCreateStatus;
use Ux2Dev\Borica\InfopayErp\Http\HttpTransport;
use Ux2Dev\Borica\InfopayErp\Resource\InvoicesResource;
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

test('invoice number must be 10 digits', function () {
    new InvoiceCreateRequest(
        number: '123',
        taxEventDate: new DateTimeImmutable('2026-04-01'),
        invoiceDate: new DateTimeImmutable('2026-04-01'),
        language: Language::Bg,
        customer: new Customer('123456789', 'Acme', new Address('BG', 'Sofia')),
        currency: Currency::Eur,
        content: new ContentWithVat([], [], new AmountPayable(0.0, 0.0, 0.0)),
        paymentDetails: new PaymentDetails(new BankTransfer([new BankTransferAccount('Bank', 'BG1', 'EUR')])),
        numberSeriesId: 'series-1',
    );
})->throws(InvalidArgumentException::class);

test('create POSTs invoice with polymorphic content + payment method serialized correctly', function () {
    $client = new FakeHttpClient([
        FakeHttpClient::json(201, ['invoiceId' => 'inv-1', 'number' => '0000000001']),
    ]);
    $resource = new InvoicesResource($this->config, new HttpTransport($client, $this->factory, $this->factory));

    $request = new InvoiceCreateRequest(
        number: '0000000001',
        taxEventDate: new DateTimeImmutable('2026-04-01'),
        invoiceDate: new DateTimeImmutable('2026-04-01'),
        language: Language::Bg,
        customer: new Customer('1234567890', 'Acme', new Address('BG', 'Sofia', 'Vitosha 1')),
        currency: Currency::Eur,
        content: new ContentWithVat(
            items: [
                new ItemWithVat(
                    name: 'Service',
                    measureUnit: 'hour',
                    quantity: 1.0,
                    unitPrice: 100.0,
                    vatRate: new NonZeroVat(20.0),
                    amount: 100.0,
                    vatAmount: 20.0,
                    amountVatIncluded: 120.0,
                ),
            ],
            amountsByVatGroup: [new AmountsByVatGroup(20.0, 100.0, 20.0, 120.0)],
            amountPayable: new AmountPayable(100.0, 20.0, 120.0),
        ),
        paymentDetails: new PaymentDetails(
            paymentMethod: new BankTransfer(
                accounts: [new BankTransferAccount('B-Trust', 'BG1', 'EUR')],
                paymentOrderDetails: 'invoice 1',
            ),
        ),
        numberSeriesId: 'series-1',
    );

    $result = $resource->create($this->session, $request);

    expect($result->invoiceId)->toBe('inv-1');

    $body = json_decode((string) $client->captured[0]->getBody(), true);
    expect($body['number'])->toBe('0000000001');
    expect($body['content']['contentType'])->toBe('contentWithVAT');
    expect($body['content']['items'][0]['vatRate']['vatRateType'])->toBe('nonZeroVAT');
    expect($body['content']['items'][0]['vatRate']['percentage'])->toBe(20.0);
    expect($body['paymentDetails']['paymentMethod']['paymentType'])->toBe('bankTransfer');
    expect($body['paymentDetails']['paymentMethod']['accounts'][0]['iban'])->toBe('BG1');
});
