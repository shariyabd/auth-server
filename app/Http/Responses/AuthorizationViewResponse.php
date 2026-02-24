<?php

namespace App\Http\Responses;

use Illuminate\Contracts\Support\Responsable;
use Laravel\Passport\Contracts\AuthorizationViewResponse as AuthorizationViewResponseContract;

class AuthorizationViewResponse implements AuthorizationViewResponseContract
{
    

    protected array $parameters = [];

    

    public function withParameters($parameters = []): static
    {
        $this->parameters = $parameters;

        return $this;
    }

    

    public function toResponse($request)
    {
        return response()->view('vendor.passport.authorize', $this->parameters);
    }
}