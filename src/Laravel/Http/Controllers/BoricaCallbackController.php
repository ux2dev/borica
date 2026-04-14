<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Laravel\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Ux2Dev\Borica\Enum\TransactionType;
use Ux2Dev\Borica\Laravel\Events\BoricaPaymentFailed;
use Ux2Dev\Borica\Laravel\Events\BoricaPaymentSucceeded;
use Ux2Dev\Borica\Laravel\Events\BoricaPreAuthFailed;
use Ux2Dev\Borica\Laravel\Events\BoricaPreAuthSucceeded;
use Ux2Dev\Borica\Laravel\Events\BoricaResponseReceived;
use Ux2Dev\Borica\Response\Response;

class BoricaCallbackController
{
    public function __invoke(Request $request): RedirectResponse
    {
        /** @var Response $response */
        $response = $request->attributes->get('borica_response');
        $merchantName = $request->attributes->get('borica_merchant_name');
        $transactionType = $request->attributes->get('borica_transaction_type');

        BoricaResponseReceived::dispatch($response, $response->getTerminal(), $merchantName);

        $this->dispatchSpecificEvent($response, $transactionType, $merchantName);

        $redirectPath = $response->isSuccessful()
            ? config('borica.redirect.success', '/payment/success')
            : config('borica.redirect.failure', '/payment/failure');

        return redirect($redirectPath);
    }

    private function dispatchSpecificEvent(Response $response, TransactionType $transactionType, ?string $merchantName): void
    {
        $successful = $response->isSuccessful();

        match ($transactionType) {
            TransactionType::Purchase => $successful
                ? BoricaPaymentSucceeded::dispatch($response, $merchantName)
                : BoricaPaymentFailed::dispatch($response, $merchantName),
            TransactionType::PreAuth => $successful
                ? BoricaPreAuthSucceeded::dispatch($response, $merchantName)
                : BoricaPreAuthFailed::dispatch($response, $merchantName),
            default => null,
        };
    }
}
