<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Cgi\Resource;

use Psr\Log\LoggerInterface;
use Ux2Dev\Borica\Cgi\Request\Input\StatusInput;
use Ux2Dev\Borica\Cgi\Request\StatusCheckRequest;
use Ux2Dev\Borica\Cgi\Support\SignsRequests;
use Ux2Dev\Borica\Cgi\Support\Validator;
use Ux2Dev\Borica\Config\CgiConfig;
use Ux2Dev\Borica\Signing\MacGeneral;
use Ux2Dev\Borica\Signing\Signer;

final class StatusResource
{
    use SignsRequests;

    public function __construct(
        protected readonly CgiConfig $config,
        protected readonly MacGeneral $macGeneral,
        protected readonly Signer $signer,
        protected readonly LoggerInterface $logger,
    ) {}

    public function check(StatusInput $input): StatusCheckRequest
    {
        Validator::order($input->order);
        $nonce = Validator::resolveNonce($input->nonce);

        $request = new StatusCheckRequest(
            terminal: $this->config->terminal,
            order: $input->order,
            nonce: $nonce,
            pSign: '',
            tranTrtype: (string) $input->transactionType->value,
        );

        return $this->signRequest($request);
    }
}
