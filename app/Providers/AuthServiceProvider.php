<?php

namespace App\Providers;

use App\Http\Responses\AuthorizationViewResponse;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Contracts\AuthorizationViewResponse as AuthorizationViewResponseContract;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    

    protected $policies = [
        
    ];

    

    public function boot(): void
    {

        $this->registerPolicies();

        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Passport::personalAccessTokensExpireIn(now()->addMonths(6));

        Passport::enableImplicitGrant();

    
        Passport::tokensCan([
            'user-read' => 'Read user profile information',
            'user-email' => 'Access user email address',
        ]);

        Passport::setDefaultScope([
            'user-read',
            'user-email',
        ]);
    }

    

    public function register(): void
    {
        $this->app->singleton(
            AuthorizationViewResponseContract::class,
            AuthorizationViewResponse::class
        );
    }
}