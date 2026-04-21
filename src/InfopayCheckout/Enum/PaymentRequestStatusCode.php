<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayCheckout\Enum;

enum PaymentRequestStatusCode: string
{
    case New = 'New';
    case Expired = 'Expired';
    case Canceled = 'Canceled';
    case PaymentCreated = 'PaymentCreated';
    case Locked = 'Locked';
    case Rejected = 'Rejected';
    case CanceledByMerchant = 'CanceledByMerchant';
}
