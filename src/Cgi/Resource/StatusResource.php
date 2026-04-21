<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Cgi\Resource;

use Psr\Log\LoggerInterface;
use Ux2Dev\Borica\Cgi\Request\StatusCheckRequest;
use Ux2Dev\Borica\Cgi\Support\SignsRequests;
use Ux2Dev\Borica\Cgi\Support\Validator;
use Ux2Dev\Borica\Config\MerchantConfig;
use Ux2Dev\Borica\Enum\TransactionType;
use Ux2Dev\Borica\Signing\MacGeneral;
use Ux2Dev\Borica\Signing\Signer;

final class StatusResource
{
    use SignsRequests;

    public function __construct(
        protected readonly MerchantConfig $config,
        protected readonly MacGeneral $macGeneral,
        protected readonly Signer $signer,
        protected readonly LoggerInterface $logger,
    ) {}

    public function check(
        string $order,
        TransactionType $transactionType,
        ?string $nonce = null,
    ): StatusCheckRequest {
        Validator::order($order);
        $nonce = Validator::resolveNonce($nonce);

        $request = new StatusCheckRequest(
            terminal: $this->config->terminal,
            order: $order,
            nonce: $nonce,
            pSign: '',
            tranTrtype: (string) $transactionType->value,
        );

        return $this->signRequest($request);
    }
}
