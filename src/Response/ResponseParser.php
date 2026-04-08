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
    ) {
    }

    public function parse(array $data, TransactionType $transactionType): Response
    {
        $pSign = $data['P_SIGN'] ?? '';

        if ($pSign === '') {
            throw new InvalidResponseException('Missing P_SIGN in response', $data);
        }

        $signingFields = $data;

        // BORICA signs StatusCheck responses with CURRENCY=USD even when the
        // POST body sends an empty CURRENCY field. Substitute it before
        // building the MAC so that P_SIGN verification succeeds.
        if ($transactionType === TransactionType::StatusCheck) {
            $currency = $signingFields['CURRENCY'] ?? '';
            if ($currency === '') {
                $signingFields['CURRENCY'] = 'USD';
            }
        }

        $signingData = $this->macGeneral->buildResponseSigningData($signingFields);

        if (!$this->signer->verify($signingData, $pSign, $this->publicKey)) {
            throw new InvalidResponseException('P_SIGN verification failed', $data);
        }

        return new Response($data);
    }
}
