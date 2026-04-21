<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Ux2Dev\Borica\Laravel\BoricaManager;

/**
 * @method static void resolveTerminalUsing(callable $resolver)
 * @method static \Ux2Dev\Borica\Cgi\CgiClient cgi(string|array|null $name = null)
 * @method static \Ux2Dev\Borica\InfopayCheckout\CheckoutClient checkout(string|array|null $name = null)
 * @method static \Ux2Dev\Borica\Cgi\CgiClient merchant(string|array|null $name = null)
 * @method static \Ux2Dev\Borica\Cgi\CgiClient|null merchantByTerminal(string $terminal)
 * @method static string|null findMerchantNameByTerminal(string $terminal)
 * @method static string getGatewayUrl()
 * @method static \Ux2Dev\Borica\Cgi\Resource\PaymentsResource payments()
 * @method static \Ux2Dev\Borica\Cgi\Resource\PreAuthResource preAuth()
 * @method static \Ux2Dev\Borica\Cgi\Resource\StatusResource status()
 * @method static \Ux2Dev\Borica\Cgi\Resource\ResponsesResource responses()
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
