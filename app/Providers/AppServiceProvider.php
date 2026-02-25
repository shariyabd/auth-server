<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{


    public function register(): void
    {

    }



    public function boot(): void
    {

        session()->flash('success', 'Welcome to the application!');
    }
}
