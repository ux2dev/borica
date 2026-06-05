<?php

declare(strict_types=1);

use Ux2Dev\Borica\Cgi\Request\PaymentRequest;
use Ux2Dev\Borica\Cgi\Request\RequestInterface;
use Ux2Dev\Borica\Contracts\Arrayable;
use Ux2Dev\Borica\Contracts\FormRequest;

it('FormRequest extends Arrayable and declares signingFields', function () {
    expect(is_subclass_of(FormRequest::class, Arrayable::class))->toBeTrue();
    expect(method_exists(FormRequest::class, 'signingFields'))->toBeTrue();
});

it('the CGI RequestInterface is a FormRequest', function () {
    expect(is_subclass_of(RequestInterface::class, FormRequest::class))->toBeTrue();
});

it('an existing CGI wire request implements FormRequest', function () {
    expect(is_subclass_of(PaymentRequest::class, FormRequest::class))->toBeTrue();
});
