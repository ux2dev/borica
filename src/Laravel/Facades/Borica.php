<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Ux2Dev\Borica\Laravel\BoricaManager;

/**
 * @method static void resolveTerminalUsing(callable $resolver)
 * @method static \Ux2Dev\Borica\Borica merchant(string|array $name = null)
 * @method static \Ux2Dev\Borica\Borica|null merchantByTerminal(string $terminal)
 * @method static string getGatewayUrl()
 * @method static \Ux2Dev\Borica\Request\PaymentRequest createPaymentRequest(string $amount, string $order, string $description, array $mInfo, string $adCustBorOrderId = '', string $language = 'BG', string $email = '', string $merchantUrl = '', ?string $timestamp = null, ?string $nonce = null)
 * @method static \Ux2Dev\Borica\Request\PreAuthRequest createPreAuthRequest(string $amount, string $order, string $description, array $mInfo, string $adCustBorOrderId = '', string $language = 'BG', string $email = '', string $merchantUrl = '', ?string $timestamp = null, ?string $nonce = null)
 * @method static \Ux2Dev\Borica\Request\PreAuthCompleteRequest createPreAuthCompleteRequest(string $amount, string $order, string $rrn, string $intRef, string $description, string $adCustBorOrderId = '', string $language = 'BG', string $email = '', string $merchantUrl = '', ?string $timestamp = null, ?string $nonce = null)
 * @method static \Ux2Dev\Borica\Request\PreAuthReversalRequest createPreAuthReversalRequest(string $amount, string $order, string $rrn, string $intRef, string $description, string $adCustBorOrderId = '', string $language = 'BG', string $email = '', string $merchantUrl = '', ?string $timestamp = null, ?string $nonce = null)
 * @method static \Ux2Dev\Borica\Request\ReversalRequest createReversalRequest(string $amount, string $order, string $rrn, string $intRef, string $description, string $adCustBorOrderId = '', string $language = 'BG', string $email = '', string $merchantUrl = '', ?string $timestamp = null, ?string $nonce = null)
 * @method static \Ux2Dev\Borica\Request\StatusCheckRequest createStatusCheckRequest(string $order, \Ux2Dev\Borica\Enum\TransactionType $transactionType, ?string $nonce = null)
 * @method static \Ux2Dev\Borica\Response\Response parseResponse(array $data, \Ux2Dev\Borica\Enum\TransactionType $transactionType, ?string $publicKey = null)
 *
 * @see \Ux2Dev\Borica\Laravel\BoricaManager
 */
class Borica extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return BoricaManager::class;
    }
}
