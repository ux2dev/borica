<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Ux2Dev\Borica\Laravel\BoricaManager;

/**
 * @method static \Ux2Dev\Borica\Borica client()
 * @method static \Ux2Dev\Borica\Laravel\BoricaManager tenant(string $name)
 * @method static string currentTenant()
 * @method static void resolveTerminalUsing(callable $resolver)
 * @method static string|null tenantByTerminal(string $terminal)
 * @method static \Ux2Dev\Borica\Cgi\CgiArea cgi()
 * @method static \Ux2Dev\Borica\InfopayCheckout\CheckoutArea checkout()
 * @method static \Ux2Dev\Borica\InfopayErp\ErpArea erp()
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
