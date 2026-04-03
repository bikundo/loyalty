<div class="space-y-8">
    <div>
        <flux:heading size="xl" level="1">Dashboard</flux:heading>
        <flux:subheading>Welcome back to {{ $tenantName }} overview.</flux:subheading>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <flux:card class="space-y-2">
            <div class="flex items-center gap-2 text-zinc-500 dark:text-zinc-400">
                <flux:icon icon="users" variant="micro" />
                <span class="text-sm font-medium">Total Customers</span>
            </div>
            <div class="text-2xl font-semibold">{{ number_format($stats['customers']) }}</div>
        </flux:card>

        <flux:card class="space-y-2">
            <div class="flex items-center gap-2 text-primary-600">
                <flux:icon icon="gift" variant="micro" />
                <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Points Issued Today</span>
            </div>
            <div class="text-2xl font-semibold">{{ number_format($stats['points_today']) }}</div>
        </flux:card>

        <flux:card class="space-y-2">
            <div class="flex items-center gap-2 text-zinc-500 dark:text-zinc-400">
                <flux:icon icon="arrow-path" variant="micro" />
                <span class="text-sm font-medium">Redemptions Today</span>
            </div>
            <div class="text-2xl font-semibold">{{ number_format($stats['redemptions_today']) }}</div>
        </flux:card>
    </div>

    <div class="space-y-4">
        <flux:heading level="2">Recent Activity</flux:heading>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>Customer</flux:table.column>
                <flux:table.column>Type</flux:table.column>
                <flux:table.column>Amount</flux:table.column>
                <flux:table.column>Date</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($recentTransactions as $tx)
                    <flux:table.row>
                        <flux:table.cell>
                            <div class="font-medium">{{ $tx->customer->name }}</div>
                            <div class="text-xs text-zinc-500">{{ $tx->customer->phone }}</div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$tx->type === 'earn' ? 'green' : 'amber'">{{ ucfirst($tx->type) }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="font-mono">{{ $tx->points > 0 ? '+' : '' }}{{ number_format($tx->points) }}</flux:table.cell>
                        <flux:table.cell class="text-xs text-zinc-500">{{ $tx->created_at->diffForHumans() }}</flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>
</div>
