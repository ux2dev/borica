<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Cgi\Resource;

use Psr\Log\LoggerInterface;
use Ux2Dev\Borica\Cgi\Request\Input\PaymentInput;
use Ux2Dev\Borica\Cgi\Request\Input\ReferencedPaymentInput;
use Ux2Dev\Borica\Cgi\Request\PreAuthCompleteRequest;
use Ux2Dev\Borica\Cgi\Request\PreAuthRequest;
use Ux2Dev\Borica\Cgi\Request\PreAuthReversalRequest;
use Ux2Dev\Borica\Cgi\Support\SignsRequests;
use Ux2Dev\Borica\Cgi\Support\Validator;
use Ux2Dev\Borica\Config\CgiConfig;
use Ux2Dev\Borica\Enum\TransactionType;
use Ux2Dev\Borica\Signing\MacGeneral;
use Ux2Dev\Borica\Signing\Signer;

final class PreAuthResource
{
    use SignsRequests;

    public function __construct(
        protected readonly CgiConfig $config,
        protected readonly MacGeneral $macGeneral,
        protected readonly Signer $signer,
        protected readonly LoggerInterface $logger,
    ) {}

    public function create(PaymentInput $input): PreAuthRequest
    {
        Validator::amount($input->amount);
        Validator::order($input->order);
        Validator::description($input->description);
        Validator::mInfo($input->mInfo);
        Validator::email($input->email);
        Validator::merchantUrl($input->merchantUrl);
        $timestamp = Validator::resolveTimestamp($input->timestamp);
        $nonce = Validator::resolveNonce($input->nonce);

        $request = new PreAuthRequest(
            terminal: $this->config->terminal,
            trtype: (string) TransactionType::PreAuth->value,
            amount: $input->amount,
            currency: $this->config->currency->value,
            order: $input->order,
            timestamp: $timestamp,
            nonce: $nonce,
            pSign: '',
            merchant: $this->config->merchantId,
            merchantName: $this->config->merchantName,
            description: $input->description,
            adCustBorOrderId: Validator::resolveAdCustBorOrderId($input->adCustBorOrderId, $input->order),
            country: $this->config->country,
            merchGmt: $this->config->timezoneOffset,
            addendum: 'AD,TD',
            email: $input->email,
            merchantUrl: $input->merchantUrl,
            language: $input->language,
            mInfo: Validator::encodeMInfo($input->mInfo),
        );

        return $this->signRequest($request);
    }

    public function complete(ReferencedPaymentInput $input): PreAuthCompleteRequest
    {
        Validator::amount($input->amount);
        Validator::order($input->order);
        Validator::description($input->description);
        Validator::email($input->email);
        Validator::merchantUrl($input->merchantUrl);
        $timestamp = Validator::resolveTimestamp($input->timestamp);
        $nonce = Validator::resolveNonce($input->nonce);

        $request = new PreAuthCompleteRequest(
            terminal: $this->config->terminal,
            amount: $input->amount,
            currency: $this->config->currency->value,
            order: $input->order,
            timestamp: $timestamp,
            nonce: $nonce,
            pSign: '',
            merchant: $this->config->merchantId,
            merchantName: $this->config->merchantName,
            description: $input->description,
            rrn: $input->rrn,
            intRef: $input->intRef,
            adCustBorOrderId: Validator::resolveAdCustBorOrderId($input->adCustBorOrderId, $input->order),
            country: $this->config->country,
            merchGmt: $this->config->timezoneOffset,
            addendum: 'AD,TD',
            email: $input->email,
            merchantUrl: $input->merchantUrl,
            language: $input->language,
        );

        return $this->signRequest($request);
    }

    public function reverse(ReferencedPaymentInput $input): PreAuthReversalRequest
    {
        Validator::amount($input->amount);
        Validator::order($input->order);
        Validator::description($input->description);
        Validator::email($input->email);
        Validator::merchantUrl($input->merchantUrl);
        $timestamp = Validator::resolveTimestamp($input->timestamp);
        $nonce = Validator::resolveNonce($input->nonce);

        $request = new PreAuthReversalRequest(
            terminal: $this->config->terminal,
            amount: $input->amount,
            currency: $this->config->currency->value,
            order: $input->order,
            timestamp: $timestamp,
            nonce: $nonce,
            pSign: '',
            merchant: $this->config->merchantId,
            merchantName: $this->config->merchantName,
            description: $input->description,
            rrn: $input->rrn,
            intRef: $input->intRef,
            adCustBorOrderId: Validator::resolveAdCustBorOrderId($input->adCustBorOrderId, $input->order),
            country: $this->config->country,
            merchGmt: $this->config->timezoneOffset,
            addendum: 'AD,TD',
            email: $input->email,
            merchantUrl: $input->merchantUrl,
            language: $input->language,
        );

        return $this->signRequest($request);
    }
}
