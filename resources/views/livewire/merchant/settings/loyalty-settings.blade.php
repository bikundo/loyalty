<div class="max-w-2xl space-y-6">
    <div>
        <flux:heading size="xl" level="1">Program Rules</flux:heading>
        <flux:subheading>Configure how your loyalty program operates globally.</flux:subheading>
    </div>

    <flux:separator />

    <form wire:submit="save" class="space-y-6">
        <flux:card class="space-y-6">
            <div>
                <flux:heading level="2">Earning & Expiry</flux:heading>
                <flux:subheading>Basic rules for point accrual and validity.</flux:subheading>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <flux:input wire:model="points_to_kes_ratio" label="Points per 1 KES spend" type="number" min="1" required />
                <flux:input wire:model="expiry_days" label="Points Expiry (Days)" type="number" min="0" placeholder="0 for no expiry" required />
            </div>

            <flux:separator variant="subtle" />

            <div>
                <flux:heading level="2">Referral Programme</flux:heading>
                <flux:subheading>Incentivize your customers to invite their friends.</flux:subheading>
            </div>

            <flux:input wire:model="referral_reward_points" label="Referral Bonus Points" type="number" min="0" placeholder="e.g. 100" />
            <p class="text-xs text-zinc-500">Points awarded to the <strong>referrer</strong> once their invited friend makes their first visit.</p>

        </flux:card>

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary">Save Configuration</flux:button>
        </div>
    </form>
</div>
