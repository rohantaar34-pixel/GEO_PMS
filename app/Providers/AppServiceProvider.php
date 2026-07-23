<?php

namespace App\Providers;

use App\Models\MaterialRequest;
use App\Models\SystemSetting;
use App\Policies\MaterialRequestPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(MaterialRequest::class, MaterialRequestPolicy::class);
        View::composer('*', function ($view): void {
            $view->with('systemSettings', SystemSetting::current());
        });

        if (config('app.env') === 'production') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
