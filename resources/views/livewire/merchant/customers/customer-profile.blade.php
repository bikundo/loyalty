<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <flux:button variant="subtle" icon="arrow-left" href="{{ route('admin.customers') }}" />
            <div>
                <flux:heading size="xl" level="1">{{ $customer->name }}</flux:heading>
                <flux:subheading>Customer Profile & History</flux:subheading>
            </div>
        </div>
        
        <div class="flex gap-2">
            <flux:button icon="pencil" variant="ghost">Edit</flux:button>
            <flux:modal.trigger name="award-points-modal">
                <flux:button icon="gift" variant="primary">Award Points</flux:button>
            </flux:modal.trigger>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <flux:card class="space-y-2">
            <div class="flex items-center gap-2 text-zinc-500 dark:text-zinc-400">
                <flux:icon icon="phone" variant="micro" />
                <span class="text-sm font-medium">Contact Summary</span>
            </div>
            <div class="text-lg font-semibold">{{ $customer->phone }}</div>
            <div class="text-sm text-zinc-500">Enrolled {{ $customer->enrolled_at ? $customer->enrolled_at->diffForHumans() : 'Unknown' }}</div>
        </flux:card>

        <flux:card class="space-y-2">
            <div class="flex items-center gap-2 text-primary-600">
                <flux:icon icon="star" variant="micro" />
                <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Points Balance</span>
            </div>
            <div class="text-2xl font-semibold">{{ number_format($customer->total_points) }}</div>
            <div class="text-sm text-zinc-500">Lifetime earned: {{ number_format($customer->lifetime_points_earned) }}</div>
        </flux:card>

        <flux:card class="space-y-2">
            <div class="flex items-center gap-2 text-zinc-500 dark:text-zinc-400">
                <flux:icon icon="arrow-path" variant="micro" />
                <span class="text-sm font-medium">Transactions</span>
            </div>
            <div class="text-2xl font-semibold">{{ number_format($customer->total_visits) }} Visits</div>
            <div class="text-sm text-zinc-500">{{ $customer->redemptions_count ?? 0 }} Total Redemptions</div>
        </flux:card>
    </div>

    <flux:card>
        <flux:heading level="2" class="mb-4">Transaction History</flux:heading>
        
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Type</flux:table.column>
                <flux:table.column>Description</flux:table.column>
                <flux:table.column>Points</flux:table.column>
                <flux:table.column>Date</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($transactions as $tx)
                    <flux:table.row>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$tx->type === 'earn' ? 'green' : 'amber'">
                                {{ ucfirst($tx->type) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ $tx->description ?? 'Transaction' }}</flux:table.cell>
                        <flux:table.cell class="font-mono font-medium {{ $tx->type === 'earn' ? 'text-green-600' : 'text-amber-600' }}">
                            {{ $tx->type === 'earn' ? '+' : '-' }}{{ number_format($tx->points) }}
                        </flux:table.cell>
                        <flux:table.cell class="text-sm text-zinc-500">{{ $tx->created_at->format('M j, Y h:i A') }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4" class="text-center py-6 text-zinc-500">
                            No transactions found for this customer.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <div class="mt-4">
            {{ $transactions->links() }}
        </div>
    </flux:card>

    <livewire:merchant.customers.award-points-form :customer="$customer" />
</div>
