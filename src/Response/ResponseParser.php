<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Response;

use Ux2Dev\Borica\Enum\TransactionType;
use Ux2Dev\Borica\Exception\InvalidResponseException;
use Ux2Dev\Borica\Signing\MacGeneral;
use Ux2Dev\Borica\Signing\Signer;

final class ResponseParser
{
    public function __construct(
        private readonly MacGeneral $macGeneral,
        private readonly Signer $signer,
        private readonly string $publicKey,
    ) {}

    public function parse(array $data, TransactionType $transactionType): Response
    {
        $pSign = $data['P_SIGN'] ?? '';
        if ($pSign === '') {
            throw new InvalidResponseException('Missing P_SIGN in response', $data);
        }

        $signingData = $this->macGeneral->buildResponseSigningData($transactionType, $data);

        if (!$this->signer->verify($signingData, $pSign, $this->publicKey)) {
            throw new InvalidResponseException('P_SIGN verification failed', $data);
        }

        return new Response($data);
    }
}
