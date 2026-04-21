<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Cgi\Resource;

use Psr\Log\LoggerInterface;
use Ux2Dev\Borica\Cgi\Request\PreAuthCompleteRequest;
use Ux2Dev\Borica\Cgi\Request\PreAuthRequest;
use Ux2Dev\Borica\Cgi\Request\PreAuthReversalRequest;
use Ux2Dev\Borica\Cgi\Support\SignsRequests;
use Ux2Dev\Borica\Cgi\Support\Validator;
use Ux2Dev\Borica\Config\MerchantConfig;
use Ux2Dev\Borica\Enum\TransactionType;
use Ux2Dev\Borica\Signing\MacGeneral;
use Ux2Dev\Borica\Signing\Signer;

final class PreAuthResource
{
    use SignsRequests;

    public function __construct(
        protected readonly MerchantConfig $config,
        protected readonly MacGeneral $macGeneral,
        protected readonly Signer $signer,
        protected readonly LoggerInterface $logger,
    ) {}

    /**
     * @param array<string, mixed> $mInfo
     */
    public function create(
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
        Validator::amount($amount);
        Validator::order($order);
        Validator::description($description);
        Validator::mInfo($mInfo);
        Validator::email($email);
        Validator::merchantUrl($merchantUrl);
        $timestamp = Validator::resolveTimestamp($timestamp);
        $nonce = Validator::resolveNonce($nonce);

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
            adCustBorOrderId: Validator::resolveAdCustBorOrderId($adCustBorOrderId, $order),
            country: $this->config->country,
            merchGmt: $this->config->timezoneOffset,
            addendum: 'AD,TD',
            email: $email,
            merchantUrl: $merchantUrl,
            language: $language,
            mInfo: Validator::encodeMInfo($mInfo),
        );

        return $this->signRequest($request);
    }

    public function complete(
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
        Validator::amount($amount);
        Validator::order($order);
        Validator::description($description);
        Validator::email($email);
        Validator::merchantUrl($merchantUrl);
        $timestamp = Validator::resolveTimestamp($timestamp);
        $nonce = Validator::resolveNonce($nonce);

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
            adCustBorOrderId: Validator::resolveAdCustBorOrderId($adCustBorOrderId, $order),
            country: $this->config->country,
            merchGmt: $this->config->timezoneOffset,
            addendum: 'AD,TD',
            email: $email,
            merchantUrl: $merchantUrl,
            language: $language,
        );

        return $this->signRequest($request);
    }

    public function reverse(
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
        Validator::amount($amount);
        Validator::order($order);
        Validator::description($description);
        Validator::email($email);
        Validator::merchantUrl($merchantUrl);
        $timestamp = Validator::resolveTimestamp($timestamp);
        $nonce = Validator::resolveNonce($nonce);

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
            adCustBorOrderId: Validator::resolveAdCustBorOrderId($adCustBorOrderId, $order),
            country: $this->config->country,
            merchGmt: $this->config->timezoneOffset,
            addendum: 'AD,TD',
            email: $email,
            merchantUrl: $merchantUrl,
            language: $language,
        );

        return $this->signRequest($request);
    }
}
