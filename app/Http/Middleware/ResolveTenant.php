<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Tenant;
use Illuminate\Http\Request;
use App\Services\TenantContext;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function __construct(protected TenantContext $context) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Priority: Authenticated User (Web, Cashier, Customer)
        foreach (['web', 'cashier', 'customer'] as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();

                if ($user && isset($user->tenant_id)) {
                    $tenant = Tenant::find($user->tenant_id);
                    if ($tenant instanceof Tenant) {
                        $this->context->set($tenant);

                        return $next($request);
                    }
                }
            }
        }

        // 2. Subdomain resolution (e.g. {tenant}.loyalty.test)
        $host = $request->getHost();
        $baseHost = parse_url(config('app.url'), PHP_URL_HOST);

        if ($host !== $baseHost && str_ends_with($host, '.' . $baseHost)) {
            $subdomain = str_replace('.' . $baseHost, '', $host);

            $tenant = Tenant::where('subdomain', $subdomain)
                ->where('status', 'active')
                ->first();

            if ($tenant) {
                $this->context->set($tenant);
            }
        }

        return $next($request);
    }
}
