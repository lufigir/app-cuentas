<?php

namespace App\Providers;

use App\Services\AccountsServiceClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AccountsServiceClient::class, fn () => new AccountsServiceClient(
            baseUrl: rtrim(config('services.accounts.url'), '/'),
            apiKey: (string) config('services.accounts.key'),
            timeout: (int) config('services.accounts.timeout'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
