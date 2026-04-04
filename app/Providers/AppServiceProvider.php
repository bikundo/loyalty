<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use App\Services\TenantContext;
use App\Services\Sms\SmsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use App\Services\Sms\SmsProviderInterface;
use App\Services\Sms\AfricasTalkingProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
        $this->app->alias(TenantContext::class, 'tenant.context');

        // SMS Services
        $this->app->bind(SmsProviderInterface::class, AfricasTalkingProvider::class);
        $this->app->singleton(SmsService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureSuperAdminGate();
    }

    /**
     * Super admins bypass all permission checks.
     * The Gate::before callback runs before any other authorization check.
     */
    protected function configureSuperAdminGate(): void
    {
        Gate::before(function (User $user, string $ability): ?bool {
            if ($user->hasRole(Role::SuperAdmin->value)) {
                return true;
            }

            return null;
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
