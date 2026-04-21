<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Ux2Dev\Borica\InfopayCheckout\Config\CheckoutConfig;
use Ux2Dev\Borica\InfopayCheckout\Dto\Account;
use Ux2Dev\Borica\InfopayCheckout\Dto\DomesticCreditTransferBgn;
use Ux2Dev\Borica\InfopayCheckout\Dto\InstructedAmount;
use Ux2Dev\Borica\InfopayCheckout\Dto\PaymentRequestDto;
use Ux2Dev\Borica\InfopayCheckout\Dto\Session;
use Ux2Dev\Borica\InfopayCheckout\Enum\InstructedAmountCurrency;
use Ux2Dev\Borica\InfopayCheckout\Enum\PaymentRequestStatusCode;
use Ux2Dev\Borica\InfopayCheckout\Enum\SessionCreateStatus;
use Ux2Dev\Borica\InfopayCheckout\Http\HttpTransport;
use Ux2Dev\Borica\InfopayCheckout\Http\JwsSigner;
use Ux2Dev\Borica\InfopayCheckout\Resource\PaymentRequestsResource;

function paymentsClient(array &$captured, array $queue): ClientInterface
{
    return new class($captured, $queue) implements ClientInterface {
        public function __construct(private array &$captured, private array $queue) {}
        public function sendRequest(RequestInterface $r): \Psr\Http\Message\ResponseInterface
        {
            $this->captured[] = $r;
            return array_shift($this->queue);
        }
    };
}

beforeEach(function () {
    $this->config = new CheckoutConfig(
        baseUrl: 'https://uat-api-checkout.infopay.bg',
        authId: 'a',
        authSecret: 'b',
        shopId: 'shop-1',
        privateKey: file_get_contents(__DIR__ . '/../../fixtures/test_private_key.pem'),
        certificate: file_get_contents(__DIR__ . '/../../fixtures/test_certificate.pem'),
    );
    $this->factory = new HttpFactory();
    $this->session = new Session('sess-id', 'sess-key', SessionCreateStatus::Success);

    $this->dto = new PaymentRequestDto(
        shopId: 'shop-1',
        beneficiaryDefaultAccount: new Account('BG29RZBB91550123456789'),
        instructedAmount: new InstructedAmount(150.00, InstructedAmountCurrency::Bgn),
        details: 'Order 5679',
        validTime: new DateTimeImmutable('2026-12-31T23:59:59Z'),
        externalReferenceId: 'ref-1',
        paymentDetails: new DomesticCreditTransferBgn('Invoice 123'),
    );
});

test('create sends signed body with X-JWS-Signature and parses response', function () {
    $captured = [];
    $httpClient = paymentsClient($captured, [
        new Psr7Response(201, [], json_encode([
            'paymentRequestId' => 'pay-1',
            '_links' => [
                'checkoutURL' => ['href' => 'https://checkout.example/pay-1'],
                'requestStatusURL' => ['href' => 'https://api.example/status/pay-1'],
            ],
        ])),
    ]);
    $transport = new HttpTransport($httpClient, $this->factory, $this->factory);
    $resource = new PaymentRequestsResource($this->config, $transport, new JwsSigner());

    $result = $resource->create($this->session, $this->dto);

    expect($result->paymentRequestId)->toBe('pay-1');
    expect($result->checkoutUrl)->toBe('https://checkout.example/pay-1');

    $request = $captured[0];
    expect($request->getMethod())->toBe('POST');
    expect((string) $request->getUri())->toBe('https://uat-api-checkout.infopay.bg/v1/api/paymentRequests');
    expect($request->getHeaderLine('Authorization'))->toBe('Basic ' . base64_encode('sess-id:sess-key'));

    $jws = $request->getHeaderLine('X-JWS-Signature');
    expect($jws)->not->toBe('');
    expect(substr_count($jws, '.'))->toBe(2);
    [$headerB64, $payload, $sigB64] = explode('.', $jws);
    expect($payload)->toBe(''); // detached JWS
});

test('create propagates 401 as AuthenticationException', function () {
    $captured = [];
    $httpClient = paymentsClient($captured, [new Psr7Response(401, [], '{"error":"no session"}')]);
    $transport = new HttpTransport($httpClient, $this->factory, $this->factory);
    $resource = new PaymentRequestsResource($this->config, $transport, new JwsSigner());

    expect(fn () => $resource->create($this->session, $this->dto))
        ->toThrow(\Ux2Dev\Borica\Exception\AuthenticationException::class);
});

test('getStatus GETs status endpoint and returns PaymentStatus', function () {
    $captured = [];
    $httpClient = paymentsClient($captured, [
        new Psr7Response(200, [], json_encode([
            'status' => [
                'PaymentRequestStatus' => ['Code' => 'PaymentCreated', 'IsFinal' => false],
            ],
        ])),
    ]);
    $transport = new HttpTransport($httpClient, $this->factory, $this->factory);
    $resource = new PaymentRequestsResource($this->config, $transport, new JwsSigner());

    $status = $resource->getStatus($this->session, 'pay-1');

    expect($status->paymentRequestStatus?->code)->toBe(PaymentRequestStatusCode::PaymentCreated);
    $req = $captured[0];
    expect($req->getMethod())->toBe('GET');
    expect((string) $req->getUri())->toBe('https://uat-api-checkout.infopay.bg/v1/api/paymentRequests/pay-1/status');
    expect($req->getHeaderLine('Authorization'))->toBe('Basic ' . base64_encode('sess-id:sess-key'));
});
