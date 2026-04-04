<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">SMS Campaigns</flux:heading>
            <flux:subheading>Manage bulk communications with your customers.</flux:subheading>
        </div>

        <flux:modal.trigger name="create-campaign-modal">
            <flux:button icon="plus" variant="primary">Create Campaign</flux:button>
        </flux:modal.trigger>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <flux:card class="flex flex-col gap-2">
            <flux:text size="sm" class="uppercase tracking-wider font-semibold text-zinc-500">SMS Credits</flux:text>
            <flux:heading size="xl">{{ number_format($tenant->smsWallet->credits_balance ?? 0) }}</flux:heading>
            <flux:badge color="green" size="sm" class="self-start">Active</flux:badge>
        </flux:card>

        <flux:card class="flex flex-col gap-2">
            <flux:text size="sm" class="uppercase tracking-wider font-semibold text-zinc-500">Total Sent</flux:text>
            <flux:heading size="xl">{{ number_format($tenant->campaigns->sum('recipients_sent') ?? 0) }}</flux:heading>
            <flux:text size="xs" class="text-zinc-400 font-medium">Lifetime usage</flux:text>
        </flux:card>
    </div>

    <flux:tabs variant="pills">
        <flux:tab :wire:click="'setTab(\'manual\')'" :current="$tab === 'manual'">Manual Campaigns</flux:tab>
        <flux:tab :wire:click="'setTab(\'automation\')'" :current="$tab === 'automation'">Lifecycle Automations</flux:tab>
    </flux:tabs>

    @if($tab === 'manual')
        <!-- Campaigns Table -->
        <livewire:merchant.campaigns.campaign-table />
    @else
        <!-- Lifecycle Automations -->
        <livewire:merchant.campaigns.automation-index />
    @endif

    <!-- Create Campaign Modal -->
    <livewire:merchant.campaigns.create-campaign-form />
</div>
