<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'LoyaltyOS') }} - Cashier</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance
    </head>
    <body class="min-h-screen bg-zinc-50 dark:bg-zinc-900 antialiased font-sans">
        <flux:header class="bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700 px-4 py-3">
            <flux:brand logo="https://loyaltyos.africa/logo.png" name="{{ app(\App\Services\TenantContext::class)->current()?->name ?? 'Scanner' }}" />

            <flux:spacer />

            <flux:dropdown>
                <flux:profile name="{{ auth()->user()->name }}" avatar="{{ auth()->user()->avatar_url ?? '' }}" />

                <flux:menu>
                    <flux:menu.item icon="arrow-path">Switch Tenant</flux:menu.item>
                    <flux:menu.separator />
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle">Log Out</flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        <flux:main class="max-w-md mx-auto py-8">
            {{ $slot }}
        </flux:main>

        @fluxScripts
    </body>
</html>
