<?php

declare(strict_types=1);

use Ux2Dev\Borica\Http\ApiResponse;

it('exposes first/all/isOk over typed results', function () {
    $a = (object) ['id' => 1];
    $b = (object) ['id' => 2];
    $r = new ApiResponse(status: 'OK', code: 200, count: 2, totalCount: 2, result: [$a, $b], raw: ['x' => 1]);

    expect($r->first())->toBe($a);
    expect($r->all())->toBe([$a, $b]);
    expect($r->isOk())->toBeTrue();
    expect($r->count)->toBe(2);
    expect($r->raw)->toBe(['x' => 1]);
});

it('first() is null on an empty result', function () {
    $r = new ApiResponse(status: null, code: 200, count: 0, totalCount: null, result: [], raw: []);
    expect($r->first())->toBeNull();
    expect($r->isOk())->toBeTrue();
});
