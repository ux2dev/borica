<?php

declare(strict_types=1);

namespace Ux2Dev\Borica;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Ux2Dev\Borica\Config\BoricaPublicKeys;
use Ux2Dev\Borica\Config\MerchantConfig;
use Ux2Dev\Borica\Enum\TransactionType;
use Ux2Dev\Borica\Request\RequestInterface;
use Ux2Dev\Borica\Request\PaymentRequest;
use Ux2Dev\Borica\Request\PreAuthCompleteRequest;
use Ux2Dev\Borica\Request\PreAuthRequest;
use Ux2Dev\Borica\Request\PreAuthReversalRequest;
use Ux2Dev\Borica\Request\ReversalRequest;
use Ux2Dev\Borica\Request\StatusCheckRequest;
use Ux2Dev\Borica\Response\Response;
use Ux2Dev\Borica\Response\ResponseParser;
use Ux2Dev\Borica\Signing\MacGeneral;
use Ux2Dev\Borica\Signing\Signer;

final class Borica
{
    private readonly MacGeneral $macGeneral;
    private readonly Signer $signer;
    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly MerchantConfig $config,
        ?LoggerInterface $logger = null,
    ) {
        $this->macGeneral = new MacGeneral();
        $this->signer = new Signer();
        $this->logger = $logger ?? new NullLogger();
    }

    public function getGatewayUrl(): string
    {
        return $this->config->environment->value;
    }

    public function createPaymentRequest(
        string $amount,
        string $order,
        string $description,
        string $adCustBorOrderId = '',
        ?array $mInfo = null,
        string $language = 'BG',
        string $email = '',
        string $merchantUrl = '',
        ?string $timestamp = null,
        ?string $nonce = null,
    ): PaymentRequest {
        $timestamp ??= gmdate('YmdHis');
        $nonce ??= strtoupper(bin2hex(random_bytes(16)));
        $addendum = $adCustBorOrderId !== '' ? 'AD,TD' : '';
        $mInfoEncoded = $mInfo !== null ? base64_encode(json_encode($mInfo)) : '';

        $request = new PaymentRequest(
            terminal: $this->config->terminal,
            trtype: (string) TransactionType::Purchase->value,
            amount: $amount,
            currency: $this->config->currency->value,
            order: $order,
            timestamp: $timestamp,
            nonce: $nonce,
            pSign: '',
            merchant: $this->config->merchantId,
            merchantName: $this->config->merchantName,
            description: $description,
            adCustBorOrderId: $adCustBorOrderId,
            country: $this->config->country,
            merchGmt: $this->config->timezoneOffset,
            addendum: $addendum,
            email: $email,
            merchantUrl: $merchantUrl,
            language: $language,
            mInfo: $mInfoEncoded,
        );

        return $this->signRequest($request);
    }

    public function createPreAuthRequest(
        string $amount,
        string $order,
        string $description,
        string $adCustBorOrderId = '',
        ?array $mInfo = null,
        string $language = 'BG',
        string $email = '',
        string $merchantUrl = '',
        ?string $timestamp = null,
        ?string $nonce = null,
    ): PreAuthRequest {
        $timestamp ??= gmdate('YmdHis');
        $nonce ??= strtoupper(bin2hex(random_bytes(16)));
        $addendum = $adCustBorOrderId !== '' ? 'AD,TD' : '';
        $mInfoEncoded = $mInfo !== null ? base64_encode(json_encode($mInfo)) : '';

        $request = new PreAuthRequest(
            terminal: $this->config->terminal,
            trtype: (string) TransactionType::PreAuth->value,
            amount: $amount,
            currency: $this->config->currency->value,
            order: $order,
            timestamp: $timestamp,
            nonce: $nonce,
            pSign: '',
            merchant: $this->config->merchantId,
            merchantName: $this->config->merchantName,
            description: $description,
            adCustBorOrderId: $adCustBorOrderId,
            country: $this->config->country,
            merchGmt: $this->config->timezoneOffset,
            addendum: $addendum,
            email: $email,
            merchantUrl: $merchantUrl,
            language: $language,
            mInfo: $mInfoEncoded,
        );

        return $this->signRequest($request);
    }

    public function createPreAuthCompleteRequest(
        string $amount,
        string $order,
        string $rrn,
        string $intRef,
        string $description,
        string $adCustBorOrderId = '',
        string $language = 'BG',
        string $email = '',
        string $merchantUrl = '',
        ?string $timestamp = null,
        ?string $nonce = null,
    ): PreAuthCompleteRequest {
        $timestamp ??= gmdate('YmdHis');
        $nonce ??= strtoupper(bin2hex(random_bytes(16)));
        $addendum = $adCustBorOrderId !== '' ? 'AD,TD' : '';

        $request = new PreAuthCompleteRequest(
            terminal: $this->config->terminal,
            amount: $amount,
            currency: $this->config->currency->value,
            order: $order,
            timestamp: $timestamp,
            nonce: $nonce,
            pSign: '',
            merchant: $this->config->merchantId,
            merchantName: $this->config->merchantName,
            description: $description,
            rrn: $rrn,
            intRef: $intRef,
            adCustBorOrderId: $adCustBorOrderId,
            country: $this->config->country,
            merchGmt: $this->config->timezoneOffset,
            addendum: $addendum,
            email: $email,
            merchantUrl: $merchantUrl,
            language: $language,
        );

        return $this->signRequest($request);
    }

    public function createPreAuthReversalRequest(
        string $amount,
        string $order,
        string $rrn,
        string $intRef,
        string $description,
        string $adCustBorOrderId = '',
        string $language = 'BG',
        string $email = '',
        string $merchantUrl = '',
        ?string $timestamp = null,
        ?string $nonce = null,
    ): PreAuthReversalRequest {
        $timestamp ??= gmdate('YmdHis');
        $nonce ??= strtoupper(bin2hex(random_bytes(16)));
        $addendum = $adCustBorOrderId !== '' ? 'AD,TD' : '';

        $request = new PreAuthReversalRequest(
            terminal: $this->config->terminal,
            amount: $amount,
            currency: $this->config->currency->value,
            order: $order,
            timestamp: $timestamp,
            nonce: $nonce,
            pSign: '',
            merchant: $this->config->merchantId,
            merchantName: $this->config->merchantName,
            description: $description,
            rrn: $rrn,
            intRef: $intRef,
            adCustBorOrderId: $adCustBorOrderId,
            country: $this->config->country,
            merchGmt: $this->config->timezoneOffset,
            addendum: $addendum,
            email: $email,
            merchantUrl: $merchantUrl,
            language: $language,
        );

        return $this->signRequest($request);
    }

    public function createReversalRequest(
        string $amount,
        string $order,
        string $rrn,
        string $intRef,
        string $description,
        string $adCustBorOrderId = '',
        string $language = 'BG',
        string $email = '',
        string $merchantUrl = '',
        ?string $timestamp = null,
        ?string $nonce = null,
    ): ReversalRequest {
        $timestamp ??= gmdate('YmdHis');
        $nonce ??= strtoupper(bin2hex(random_bytes(16)));
        $addendum = $adCustBorOrderId !== '' ? 'AD,TD' : '';

        $request = new ReversalRequest(
            terminal: $this->config->terminal,
            amount: $amount,
            currency: $this->config->currency->value,
            order: $order,
            timestamp: $timestamp,
            nonce: $nonce,
            pSign: '',
            merchant: $this->config->merchantId,
            merchantName: $this->config->merchantName,
            description: $description,
            rrn: $rrn,
            intRef: $intRef,
            adCustBorOrderId: $adCustBorOrderId,
            country: $this->config->country,
            merchGmt: $this->config->timezoneOffset,
            addendum: $addendum,
            email: $email,
            merchantUrl: $merchantUrl,
            language: $language,
        );

        return $this->signRequest($request);
    }

    public function createStatusCheckRequest(
        string $order,
        TransactionType $transactionType,
        ?string $nonce = null,
    ): StatusCheckRequest {
        $nonce ??= strtoupper(bin2hex(random_bytes(16)));

        $request = new StatusCheckRequest(
            terminal: $this->config->terminal,
            order: $order,
            nonce: $nonce,
            pSign: '',
            tranTrtype: (string) $transactionType->value,
        );

        return $this->signRequest($request);
    }

    public function parseResponse(
        array $data,
        TransactionType $transactionType,
        ?string $publicKey = null,
    ): Response {
        $key = $publicKey ?? BoricaPublicKeys::getPublicKey($this->config->environment);
        $parser = new ResponseParser($this->macGeneral, $this->signer, $key);
        return $parser->parse($data, $transactionType);
    }

    /**
     * @template T of RequestInterface
     * @param T $request
     * @return T
     */
    private function signRequest(RequestInterface $request): RequestInterface
    {
        $signingData = $this->macGeneral->buildRequestSigningData(
            $request->getTransactionType(),
            $request->getSigningFields(),
        );
        $pSign = $this->signer->sign(
            $signingData,
            $this->config->privateKey,
            $this->config->privateKeyPassphrase,
        );
        $this->logger->debug('Signed BORICA request', ['trtype' => $request->getTransactionType()->value]);

        $class = new \ReflectionClass($request);
        $params = [];
        foreach ($class->getConstructor()->getParameters() as $param) {
            $name = $param->getName();
            $prop = $class->getProperty($name);
            $value = $prop->getValue($request);
            $params[$name] = $name === 'pSign' ? $pSign : $value;
        }
        return $class->newInstanceArgs($params);
    }
}
