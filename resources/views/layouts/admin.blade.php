<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'LoyaltyOS') }} - {{ $title ?? '' }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-900 antialiased font-sans">
        <flux:sidebar sticky stashable class="bg-zinc-50 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-800">
            <flux:sidebar.toggle class="lg:hidden" />

            <flux:brand href="/admin/dashboard" logo="https://loyaltyos.africa/logo.png" name="{{ app(\App\Services\TenantContext::class)->current()?->name ?? 'LoyaltyOS' }}" class="px-2" />

            <flux:navlist variant="outline">
                <flux:navlist.item icon="home" href="/admin/dashboard" :current="request()->is('admin/dashboard')">Dashboard</flux:navlist.item>
                <flux:navlist.item icon="users" href="/admin/customers" :current="request()->is('admin/customers*')">Customers</flux:navlist.item>
                <flux:navlist.item icon="gift" href="/admin/rewards" :current="request()->is('admin/rewards*')">Rewards</flux:navlist.item>
                <flux:navlist.item icon="chat-bubble-bottom-center-text" href="/admin/campaigns" :current="request()->is('admin/campaigns*')">Marketing</flux:navlist.item>
                <flux:navlist.item icon="cog-6-tooth" href="/admin/settings" :current="request()->is('admin/settings*')">Settings</flux:navlist.item>
            </flux:navlist>

            <flux:spacer />

            <flux:navlist variant="outline">
                <flux:navlist.item icon="question-mark-circle" href="#">Help & Support</flux:navlist.item>
            </flux:navlist>

            <flux:dropdown position="top" align="start" class="max-lg:hidden">
                <flux:profile avatar="{{ auth()->user()->avatar_url ?? '' }}" name="{{ auth()->user()->name }}" />

                <flux:menu>
                    <flux:menu.item icon="user-circle">My Profile</flux:menu.item>
                    <flux:menu.separator />
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle">Log Out</flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <flux:header class="lg:hidden">
            <flux:sidebar.toggle />
            <flux:spacer />
            <flux:profile name="{{ auth()->user()->name }}" />
        </flux:header>

        <flux:main>
            {{ $slot }}
        </flux:main>

        <flux:toast />
        @fluxScripts
    </body>
</html>
