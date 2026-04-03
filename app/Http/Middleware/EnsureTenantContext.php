<?php

namespace App\Http\Middleware;

use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantContext
{
    public function __construct(protected TenantContext $context) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->context->isSet()) {
            // If we are on a login/registration route, we might allow it
            // but for dashboard/API routes, we abort.
            abort(Response::HTTP_NOT_FOUND, 'Tenant context not resolved.');
        }

        return $next($request);
    }
}
