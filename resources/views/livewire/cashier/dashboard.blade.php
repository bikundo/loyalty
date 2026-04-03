<div class="space-y-12">
    <div class="text-center space-y-2">
        <flux:heading level="1" size="xl">Ready to Scan</flux:heading>
        <flux:subheading>Enter a customer's phone number to award beans.</flux:subheading>
    </div>

    <form wire:submit="search" class="space-y-4">
        <flux:input 
            wire:model="search" 
            placeholder="0700 000 000" 
            size="lg" 
            icon="device-phone-mobile" 
            inputmode="numeric" 
            autofocus 
        />

        <flux:button variant="primary" size="lg" icon="magnifying-glass" block type="submit">
            Find Customer
        </flux:button>
    </form>

    <div class="space-y-4">
        <flux:heading level="2" size="sm" class="uppercase tracking-wider text-zinc-500 font-bold">Recent Today</flux:heading>

        <div class="space-y-3">
            @forelse ($recentScans as $tx)
                <flux:card class="flex items-center justify-between py-3 px-4">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 flex items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800 text-zinc-600 font-bold uppercase text-sm">
                            {{ substr($tx->customer->name, 0, 1) }}
                        </div>
                        <div>
                            <div class="font-bold text-sm">{{ $tx->customer->name }}</div>
                            <div class="text-xs text-zinc-500 font-mono">{{ $tx->points > 0 ? '+' : '' }}{{ number_format($tx->points) }} beans</div>
                        </div>
                    </div>
                    <flux:icon icon="chevron-right" variant="micro" class="text-zinc-400" />
                </flux:card>
            @empty
                <div class="text-center py-12 text-zinc-400">
                    <flux:icon icon="qr-code" variant="outline" class="mx-auto mb-2" />
                    <div class="text-sm">No scans yet today.</div>
                </div>
            @endforelse
        </div>
    </div>
</div>
