<?php

declare(strict_types=1);

use Ux2Dev\Borica\InfopayCheckout\Dto\Account;
use Ux2Dev\Borica\InfopayCheckout\Dto\BudgetPaymentDetails;
use Ux2Dev\Borica\InfopayCheckout\Dto\InstructedAmount;
use Ux2Dev\Borica\InfopayCheckout\Enum\InstructedAmountCurrency;
use Ux2Dev\Borica\InfopayCheckout\Enum\TaxPayerType;

test('Account serialises iban', function () {
    $a = new Account(iban: 'BG29RZBB91550123456789');
    expect($a->toArray())->toBe(['iban' => 'BG29RZBB91550123456789']);
});

test('InstructedAmount serialises amount and currency', function () {
    $a = new InstructedAmount(amount: 150.00, currency: InstructedAmountCurrency::Bgn);
    expect($a->toArray())->toBe(['amount' => 150.00, 'currency' => 'BGN']);
});

test('BudgetPaymentDetails serialises taxPayerId and taxPayerType', function () {
    $b = new BudgetPaymentDetails(taxPayerId: '8701263399', taxPayerType: TaxPayerType::Egn);
    expect($b->toArray())->toBe([
        'taxPayerId' => '8701263399',
        'taxPayerType' => 'EGN',
    ]);
});
