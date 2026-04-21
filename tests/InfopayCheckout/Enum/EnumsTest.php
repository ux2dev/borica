<?php

declare(strict_types=1);

use Ux2Dev\Borica\InfopayCheckout\Enum\InstructedAmountCurrency;
use Ux2Dev\Borica\InfopayCheckout\Enum\PaymentLanguage;
use Ux2Dev\Borica\InfopayCheckout\Enum\PaymentRequestStatusCode;
use Ux2Dev\Borica\InfopayCheckout\Enum\PaymentStatusCode;
use Ux2Dev\Borica\InfopayCheckout\Enum\PaymentType;
use Ux2Dev\Borica\InfopayCheckout\Enum\SepaServiceLevel;
use Ux2Dev\Borica\InfopayCheckout\Enum\ServiceLevel;
use Ux2Dev\Borica\InfopayCheckout\Enum\SessionCreateStatus;
use Ux2Dev\Borica\InfopayCheckout\Enum\SessionStatusCode;
use Ux2Dev\Borica\InfopayCheckout\Enum\TaxPayerType;

test('PaymentType has three cases', function () {
    expect(PaymentType::DomesticCreditTransfersBgn->value)->toBe('domestic-credit-transfers-bgn');
    expect(PaymentType::DomesticBudgetTransfersBgn->value)->toBe('domestic-budget-transfers-bgn');
    expect(PaymentType::SepaCreditTransfers->value)->toBe('sepa-credit-transfers');
});

test('PaymentLanguage has BG and EN', function () {
    expect(PaymentLanguage::Bg->value)->toBe('BG');
    expect(PaymentLanguage::En->value)->toBe('EN');
});

test('InstructedAmountCurrency has BGN and EUR', function () {
    expect(InstructedAmountCurrency::Bgn->value)->toBe('BGN');
    expect(InstructedAmountCurrency::Eur->value)->toBe('EUR');
});

test('ServiceLevel has NEXT URGP BLNK', function () {
    expect(ServiceLevel::Next->value)->toBe('NEXT');
    expect(ServiceLevel::Urgp->value)->toBe('URGP');
    expect(ServiceLevel::Blnk->value)->toBe('BLNK');
});

test('SepaServiceLevel has SEPA and INST', function () {
    expect(SepaServiceLevel::Sepa->value)->toBe('SEPA');
    expect(SepaServiceLevel::Inst->value)->toBe('INST');
});

test('TaxPayerType has EGN EIK PNF', function () {
    expect(TaxPayerType::Egn->value)->toBe('EGN');
    expect(TaxPayerType::Eik->value)->toBe('EIK');
    expect(TaxPayerType::Pnf->value)->toBe('PNF');
});

test('SessionCreateStatus has Success InvalidCredentials Blocked', function () {
    expect(SessionCreateStatus::Success->value)->toBe('Success');
    expect(SessionCreateStatus::InvalidCredentials->value)->toBe('InvalidCredentials');
    expect(SessionCreateStatus::Blocked->value)->toBe('Blocked');
});

test('SessionStatusCode has NoSession Expired Valid Invalid', function () {
    expect(SessionStatusCode::NoSession->value)->toBe('NoSession');
    expect(SessionStatusCode::Expired->value)->toBe('Expired');
    expect(SessionStatusCode::Valid->value)->toBe('Valid');
    expect(SessionStatusCode::Invalid->value)->toBe('Invalid');
});

test('PaymentRequestStatusCode has 7 cases', function () {
    expect(PaymentRequestStatusCode::New->value)->toBe('New');
    expect(PaymentRequestStatusCode::Expired->value)->toBe('Expired');
    expect(PaymentRequestStatusCode::Canceled->value)->toBe('Canceled');
    expect(PaymentRequestStatusCode::PaymentCreated->value)->toBe('PaymentCreated');
    expect(PaymentRequestStatusCode::Locked->value)->toBe('Locked');
    expect(PaymentRequestStatusCode::Rejected->value)->toBe('Rejected');
    expect(PaymentRequestStatusCode::CanceledByMerchant->value)->toBe('CanceledByMerchant');
});

test('PaymentStatusCode has 11 cases', function () {
    expect(PaymentStatusCode::New->value)->toBe('New');
    expect(PaymentStatusCode::WaitingForProcessing->value)->toBe('WaitingForProcessing');
    expect(PaymentStatusCode::Processed->value)->toBe('Processed');
    expect(PaymentStatusCode::WaitingForProcessingWithFutureValue->value)->toBe('WaitingForProcessingWithFutureValue');
    expect(PaymentStatusCode::ProcessedInterbank->value)->toBe('ProcessedInterbank');
    expect(PaymentStatusCode::Cancelled->value)->toBe('Cancelled');
    expect(PaymentStatusCode::Rejected->value)->toBe('Rejected');
    expect(PaymentStatusCode::Executed->value)->toBe('Executed');
    expect(PaymentStatusCode::InsufficientFunds->value)->toBe('InsufficientFunds');
    expect(PaymentStatusCode::PartiallyProcessed->value)->toBe('PartiallyProcessed');
    expect(PaymentStatusCode::RejectedCancelled->value)->toBe('Rejected_Cancelled');
});
