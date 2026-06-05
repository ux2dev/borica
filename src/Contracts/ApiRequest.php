<?php

declare(strict_types=1);

namespace Ux2Dev\Borica\Contracts;

/** A request dispatched over HTTP (Infopay Checkout + ERP). */
interface ApiRequest extends BoricaRequest
{
    /** HTTP verb, e.g. 'POST' or 'GET'. */
    public function method(): string;

    /** Relative path with any id/query already baked in, e.g. '/api/payments/{id}/status'. */
    public function endpoint(): string;

    /** FQCN of the BoricaResult hydrated from the response body. */
    public function responseClass(): string;
}
