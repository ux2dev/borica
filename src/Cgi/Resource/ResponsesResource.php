<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Cgi\Resource;

use Psr\Log\LoggerInterface;
use Ux2Dev\Borica\Cgi\Response\Response;
use Ux2Dev\Borica\Cgi\Response\ResponseParser;
use Ux2Dev\Borica\Config\BoricaPublicKeys;
use Ux2Dev\Borica\Config\MerchantConfig;
use Ux2Dev\Borica\Enum\TransactionType;
use Ux2Dev\Borica\Signing\MacGeneral;
use Ux2Dev\Borica\Signing\Signer;

final class ResponsesResource
{
    public function __construct(
        private readonly MerchantConfig $config,
        private readonly MacGeneral $macGeneral,
        private readonly Signer $signer,
        private readonly LoggerInterface $logger,
        private readonly ?string $boricaPublicKey = null,
    ) {}

    /**
     * Parse and verify a BORICA response.
     *
     * Verifies P_SIGN using the BORICA public key. Additional validations
     * (nonce matching, order matching, amount/currency checks) are the
     * caller's responsibility since they have the request context.
     *
     * @param array<string, mixed> $data
     * @param string|null $publicKey Override the BORICA public key (for testing).
     */
    public function parse(
        array $data,
        TransactionType $transactionType,
        ?string $publicKey = null,
    ): Response {
        $key = $publicKey ?? $this->boricaPublicKey ?? BoricaPublicKeys::getPublicKey($this->config->environment);

        $parser = new ResponseParser($this->macGeneral, $this->signer, $key);

        $this->logger->info('Parsing BORICA response', [
            'action' => $data['ACTION'] ?? '',
            'rc' => $data['RC'] ?? '',
            'order' => $data['ORDER'] ?? '',
        ]);

        return $parser->parse($data, $transactionType);
    }
}
