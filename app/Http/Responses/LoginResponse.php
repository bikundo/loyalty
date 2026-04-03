<?php

namespace App\Http\Responses;

use App\Enums\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 204);
        }

        $user = $request->user();

        // Platform Administration
        if ($user->hasRole(Role::SuperAdmin->value)) {
            return redirect()->intended('/platform/dashboard');
        }

        // Merchant Dashboards
        if ($user->hasRole([Role::MerchantOwner->value, Role::MerchantManager->value])) {
            return redirect()->intended('/admin/dashboard');
        }

        // Customer Dashboard
        if ($user->hasRole(Role::Customer->value)) {
            return redirect()->intended('/customer/dashboard');
        }

        // Default fallback (e.g. for Cashiers who log in via specific guard)
        return redirect()->intended('/');
    }
}
