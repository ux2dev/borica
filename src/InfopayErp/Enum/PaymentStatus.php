<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\InfopayErp\Enum;

enum PaymentStatus: string
{
    case New = 'New';
    case WaitingForProcessing = 'WaitingForProcessing';
    case Processed = 'Processed';
    case WaitingForProcessingWithFutureValue = 'WaitingForProcessingWithFutureValue';
    case ProcessedInterbank = 'ProcessedInterbank';
    case Cancelled = 'Cancelled';
    case Rejected = 'Rejected';
    case Executed = 'Executed';
    case InsufficientFunds = 'InsufficientFunds';
    case PartiallyProcessed = 'PartiallyProcessed';
    case RejectedCancelled = 'Rejected_Cancelled';
}
