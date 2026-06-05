<?php

declare(strict_types=1);

use Ux2Dev\Borica\Contracts\Arrayable;
use Ux2Dev\Borica\Contracts\BoricaRequest;

it('defines the Arrayable contract', function () {
    expect(interface_exists(Arrayable::class))->toBeTrue();
    expect(method_exists(Arrayable::class, 'toArray'))->toBeTrue();
});

it('BoricaRequest extends Arrayable', function () {
    expect(is_subclass_of(BoricaRequest::class, Arrayable::class))->toBeTrue();
});
