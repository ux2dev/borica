<?php
declare(strict_types=1);

namespace Ux2Dev\Borica\Cgi\Request;

use Ux2Dev\Borica\Enum\TransactionType;

interface RequestInterface
{
    public function getTransactionType(): TransactionType;
    public function toArray(): array;
    public function getSigningFields(): array;
}
