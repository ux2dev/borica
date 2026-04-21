<?php

declare(strict_types=1);

use Ux2Dev\Borica\InfopayCheckout\Dto\BudgetPaymentDetails;
use Ux2Dev\Borica\InfopayCheckout\Dto\DomesticBudgetTransferBgn;
use Ux2Dev\Borica\InfopayCheckout\Dto\DomesticCreditTransferBgn;
use Ux2Dev\Borica\InfopayCheckout\Dto\SepaCreditTransfer;
use Ux2Dev\Borica\InfopayCheckout\Enum\PaymentType;
use Ux2Dev\Borica\InfopayCheckout\Enum\SepaServiceLevel;
use Ux2Dev\Borica\InfopayCheckout\Enum\ServiceLevel;
use Ux2Dev\Borica\InfopayCheckout\Enum\TaxPayerType;

test('DomesticCreditTransferBgn serialises required field', function () {
    $d = new DomesticCreditTransferBgn(remittanceInformationUnstructured: 'Invoice 123');
    expect($d->toArray())->toBe([
        'type' => PaymentType::DomesticCreditTransfersBgn->value,
        'remittanceInformationUnstructured' => 'Invoice 123',
    ]);
});

test('DomesticCreditTransferBgn serialises optional fields when set', function () {
    $d = new DomesticCreditTransferBgn(
        remittanceInformationUnstructured: 'Invoice 123',
        serviceLevel: ServiceLevel::Blnk,
        endToEndIdentification: 'ABC123',
    );
    expect($d->toArray())->toBe([
        'type' => PaymentType::DomesticCreditTransfersBgn->value,
        'remittanceInformationUnstructured' => 'Invoice 123',
        'serviceLevel' => 'BLNK',
        'endToEndIdentification' => 'ABC123',
    ]);
});

test('DomesticBudgetTransferBgn requires ultimateDebtor and budgetPaymentDetails', function () {
    $d = new DomesticBudgetTransferBgn(
        remittanceInformationUnstructured: 'Pay invoice',
        ultimateDebtor: 'Иван Иванов',
        budgetPaymentDetails: new BudgetPaymentDetails('8701263399', TaxPayerType::Egn),
    );

    expect($d->toArray())->toBe([
        'type' => PaymentType::DomesticBudgetTransfersBgn->value,
        'remittanceInformationUnstructured' => 'Pay invoice',
        'ultimateDebtor' => 'Иван Иванов',
        'budgetPaymentDetails' => [
            'taxPayerId' => '8701263399',
            'taxPayerType' => 'EGN',
        ],
    ]);
});

test('SepaCreditTransfer uses SEPA service level enum', function () {
    $d = new SepaCreditTransfer(
        remittanceInformationUnstructured: 'SEPA payment',
        serviceLevel: SepaServiceLevel::Inst,
    );
    expect($d->toArray())->toBe([
        'type' => PaymentType::SepaCreditTransfers->value,
        'remittanceInformationUnstructured' => 'SEPA payment',
        'serviceLevel' => 'INST',
    ]);
});
