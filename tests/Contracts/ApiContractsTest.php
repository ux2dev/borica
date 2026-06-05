<?php

declare(strict_types=1);

use Ux2Dev\Borica\Contracts\ApiRequest;
use Ux2Dev\Borica\Contracts\BoricaRequest;
use Ux2Dev\Borica\Contracts\BoricaResult;

it('ApiRequest extends BoricaRequest and declares HTTP shape', function () {
    expect(is_subclass_of(ApiRequest::class, BoricaRequest::class))->toBeTrue();
    foreach (['method', 'endpoint', 'responseClass'] as $m) {
        expect(method_exists(ApiRequest::class, $m))->toBeTrue();
    }
});

it('BoricaResult declares fromArray', function () {
    expect(method_exists(BoricaResult::class, 'fromArray'))->toBeTrue();
});
