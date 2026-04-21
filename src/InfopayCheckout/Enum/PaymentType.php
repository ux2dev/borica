<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayCheckout\Enum;

enum PaymentType: string
{
    case DomesticCreditTransfersBgn = 'domestic-credit-transfers-bgn';
    case DomesticBudgetTransfersBgn = 'domestic-budget-transfers-bgn';
    case SepaCreditTransfers = 'sepa-credit-transfers';
}
