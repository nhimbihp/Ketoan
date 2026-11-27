<?php

namespace App\Providers;

use App\Documents\DocumentRegistry;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DocumentRegistry::class,
            fn ($app) => new DocumentRegistry($app, config('documents.types', [])));
    }

    public function boot(): void
    {
        Gate::define('manage-tenants', fn (User $user) => $user->isSuperAdmin());
        Gate::define('manage-company', fn (User $user) => $user->isOwner() && $user->tenant_id !== null);
    }
}
