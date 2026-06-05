<?php
declare(strict_types=1);

namespace Ux2Dev\Borica\Cgi\Request;

use Ux2Dev\Borica\Contracts\FormRequest;
use Ux2Dev\Borica\Enum\TransactionType;

interface RequestInterface extends FormRequest
{
    public function getTransactionType(): TransactionType;
    public function signingFields(): array;
}
