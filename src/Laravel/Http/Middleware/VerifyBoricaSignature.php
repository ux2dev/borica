<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Ux2Dev\Borica\Enum\TransactionType;
use Ux2Dev\Borica\Exception\BoricaException;
use Ux2Dev\Borica\Laravel\BoricaManager;

class VerifyBoricaSignature
{
    public function __construct(
        private readonly BoricaManager $manager,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $data = $request->post();
        $terminal = $data['TERMINAL'] ?? '';

        $merchantName = $this->manager->findMerchantNameByTerminal($terminal);

        if ($merchantName === null) {
            $this->logger->warning('BORICA callback from unknown terminal', [
                'terminal' => $terminal,
                'ip' => $request->ip(),
            ]);
            abort(403, 'Unknown BORICA terminal');
        }

        $trtype = (int) ($data['TRTYPE'] ?? 0);
        $transactionType = TransactionType::tryFrom($trtype);

        if ($transactionType === null) {
            $this->logger->warning('BORICA callback with invalid transaction type', [
                'terminal' => $terminal,
                'trtype' => $data['TRTYPE'] ?? '',
                'ip' => $request->ip(),
            ]);
            abort(403, 'Invalid transaction type');
        }

        try {
            $borica = $this->manager->merchant($merchantName);
            $response = $borica->responses()->parse($data, $transactionType);
        } catch (BoricaException $e) {
            $this->logger->warning('BORICA signature verification failed', [
                'terminal' => $terminal,
                'merchant' => $merchantName,
                'order' => $data['ORDER'] ?? '',
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);
            abort(403, 'BORICA signature verification failed');
        }

        $request->attributes->set('borica_response', $response);
        $request->attributes->set('borica_merchant_name', $merchantName);
        $request->attributes->set('borica_transaction_type', $transactionType);

        return $next($request);
    }
}
