<?php

declare(strict_types=1);

namespace Ux2Dev\Borica;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Ux2Dev\Borica\Config\BoricaPublicKeys;
use Ux2Dev\Borica\Config\MerchantConfig;
use Ux2Dev\Borica\Enum\Environment;
use Ux2Dev\Borica\Enum\TransactionType;
use Ux2Dev\Borica\Exception\ConfigurationException;
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
        array $mInfo,
        string $adCustBorOrderId = '',
        string $language = 'BG',
        string $email = '',
        string $merchantUrl = '',
        ?string $timestamp = null,
        ?string $nonce = null,
    ): PaymentRequest {
        $this->validateAmount($amount);
        $this->validateOrder($order);
        $this->validateDescription($description);
        $this->validateMInfo($mInfo);
        $this->validateEmail($email);
        $this->validateMerchantUrl($merchantUrl);
        $timestamp = $this->resolveTimestamp($timestamp);
        $nonce = $this->resolveNonce($nonce);
        $mInfoEncoded = $this->encodeMInfo($mInfo);

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
            adCustBorOrderId: $this->resolveAdCustBorOrderId($adCustBorOrderId, $order),
            country: $this->config->country,
            merchGmt: $this->config->timezoneOffset,
            addendum: 'AD,TD',
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
        array $mInfo,
        string $adCustBorOrderId = '',
        string $language = 'BG',
        string $email = '',
        string $merchantUrl = '',
        ?string $timestamp = null,
        ?string $nonce = null,
    ): PreAuthRequest {
        $this->validateAmount($amount);
        $this->validateOrder($order);
        $this->validateDescription($description);
        $this->validateMInfo($mInfo);
        $this->validateEmail($email);
        $this->validateMerchantUrl($merchantUrl);
        $timestamp = $this->resolveTimestamp($timestamp);
        $nonce = $this->resolveNonce($nonce);
        $mInfoEncoded = $this->encodeMInfo($mInfo);

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
            adCustBorOrderId: $this->resolveAdCustBorOrderId($adCustBorOrderId, $order),
            country: $this->config->country,
            merchGmt: $this->config->timezoneOffset,
            addendum: 'AD,TD',
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
        $this->validateAmount($amount);
        $this->validateOrder($order);
        $this->validateDescription($description);
        $this->validateEmail($email);
        $this->validateMerchantUrl($merchantUrl);
        $timestamp = $this->resolveTimestamp($timestamp);
        $nonce = $this->resolveNonce($nonce);
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
            adCustBorOrderId: $this->resolveAdCustBorOrderId($adCustBorOrderId, $order),
            country: $this->config->country,
            merchGmt: $this->config->timezoneOffset,
            addendum: 'AD,TD',
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
        $this->validateAmount($amount);
        $this->validateOrder($order);
        $this->validateDescription($description);
        $this->validateEmail($email);
        $this->validateMerchantUrl($merchantUrl);
        $timestamp = $this->resolveTimestamp($timestamp);
        $nonce = $this->resolveNonce($nonce);

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
            adCustBorOrderId: $this->resolveAdCustBorOrderId($adCustBorOrderId, $order),
            country: $this->config->country,
            merchGmt: $this->config->timezoneOffset,
            addendum: 'AD,TD',
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
        $this->validateAmount($amount);
        $this->validateOrder($order);
        $this->validateDescription($description);
        $this->validateEmail($email);
        $this->validateMerchantUrl($merchantUrl);
        $timestamp = $this->resolveTimestamp($timestamp);
        $nonce = $this->resolveNonce($nonce);

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
            adCustBorOrderId: $this->resolveAdCustBorOrderId($adCustBorOrderId, $order),
            country: $this->config->country,
            merchGmt: $this->config->timezoneOffset,
            addendum: 'AD,TD',
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
        $this->validateOrder($order);
        $nonce = $this->resolveNonce($nonce);

        $request = new StatusCheckRequest(
            terminal: $this->config->terminal,
            order: $order,
            nonce: $nonce,
            pSign: '',
            tranTrtype: (string) $transactionType->value,
        );

        return $this->signRequest($request);
    }

    /**
     * Parse and verify a BORICA response.
     *
     * Verifies P_SIGN using the BORICA public key. Additional validations
     * (nonce matching, order matching, amount/currency checks) are the
     * caller's responsibility since they have the request context.
     *
     * @param string|null $publicKey Override the BORICA public key (for testing).
     */
    public function parseResponse(
        array $data,
        TransactionType $transactionType,
        ?string $publicKey = null,
    ): Response {
        $key = $publicKey ?? BoricaPublicKeys::getPublicKey($this->config->environment);

        $parser = new ResponseParser($this->macGeneral, $this->signer, $key);

        $this->logger->info('Parsing BORICA response', [
            'action' => $data['ACTION'] ?? '',
            'rc' => $data['RC'] ?? '',
            'order' => $data['ORDER'] ?? '',
        ]);

        return $parser->parse($data, $transactionType);
    }

    private function validateAmount(string $amount): void
    {
        if (!preg_match('/^\d{1,12}\.\d{2}$/', $amount)) {
            throw new ConfigurationException(
                'Amount must be a positive number with exactly 2 decimal places (e.g. "10.50")'
            );
        }
        if (bccomp($amount, '0.00', 2) <= 0) {
            throw new ConfigurationException('Amount must be greater than zero');
        }
    }

    private function validateOrder(string $order): void
    {
        if (!preg_match('/^\d{6}$/', $order)) {
            throw new ConfigurationException('Order must be exactly 6 digits (zero-padded, e.g. "000001")');
        }
    }

    private function resolveTimestamp(?string $timestamp): string
    {
        if ($timestamp === null) {
            return gmdate('YmdHis');
        }
        if (!preg_match('/^\d{14}$/', $timestamp)) {
            throw new ConfigurationException('Timestamp must be exactly 14 digits (YmdHis format)');
        }
        return $timestamp;
    }

    private function resolveNonce(?string $nonce): string
    {
        if ($nonce === null) {
            return strtoupper(bin2hex(random_bytes(16)));
        }
        if (!preg_match('/^[A-F0-9]{32}$/', $nonce)) {
            throw new ConfigurationException('Nonce must be exactly 32 uppercase hex characters');
        }
        return $nonce;
    }

    private function validateDescription(string $description): void
    {
        if ($description === '') {
            throw new ConfigurationException('Description must not be empty');
        }
        if (mb_strlen($description) > 50) {
            throw new ConfigurationException('Description must not exceed 50 characters');
        }
    }

    private function validateEmail(string $email): void
    {
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ConfigurationException('Invalid email format');
        }
    }

    private function validateMerchantUrl(string $url): void
    {
        if ($url === '') {
            return;
        }
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new ConfigurationException('Invalid merchant URL');
        }
        if (!str_starts_with($url, 'https://')) {
            throw new ConfigurationException('Merchant URL must use HTTPS');
        }
    }

    private function validateMInfo(array $mInfo): void
    {
        if (!isset($mInfo['cardholderName']) || !is_string($mInfo['cardholderName']) || $mInfo['cardholderName'] === '') {
            throw new ConfigurationException('M_INFO must contain a non-empty "cardholderName"');
        }
        if (mb_strlen($mInfo['cardholderName']) > 45) {
            throw new ConfigurationException('M_INFO "cardholderName" must not exceed 45 characters');
        }
        $hasEmail = isset($mInfo['email']) && is_string($mInfo['email']) && $mInfo['email'] !== '';
        $hasPhone = isset($mInfo['mobilePhone']) && is_array($mInfo['mobilePhone']);
        if (!$hasEmail && !$hasPhone) {
            throw new ConfigurationException('M_INFO must contain "email" and/or "mobilePhone"');
        }
    }

    private function encodeMInfo(array $mInfo): string
    {
        $encoded = base64_encode(json_encode($mInfo, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        if (strlen($encoded) > 35000) {
            throw new ConfigurationException('M_INFO data exceeds maximum allowed size of 35000 bytes');
        }
        return $encoded;
    }

    /**
     * @template T of RequestInterface
     * @param T $request
     * @return T
     */
    private function resolveAdCustBorOrderId(string $adCustBorOrderId, string $order): string
    {
        $value = $adCustBorOrderId !== '' ? $adCustBorOrderId : $order;
        $value = str_replace(';', '', $value);
        return mb_substr($value, 0, 22);
    }

    private function signRequest(RequestInterface $request): RequestInterface
    {
        $fields = $request->getSigningFields();
        $fields['MERCHANT'] = $this->config->merchantId;

        $signingData = $this->macGeneral->buildRequestSigningData(
            $request->getTransactionType(),
            $fields,
            $this->config->signingSchema,
        );
        $pSign = $this->signer->sign(
            $signingData,
            $this->config->getPrivateKey(),
            $this->config->getPrivateKeyPassphrase(),
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
