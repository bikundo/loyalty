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
            <flux:modal.trigger name="redeem-reward-modal">
                <flux:button icon="gift-top" variant="primary">Redeem Reward</flux:button>
            </flux:modal.trigger>
            <flux:modal.trigger name="award-points-modal">
                <flux:button icon="plus" variant="filled">Award Points</flux:button>
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
                <flux:icon icon="share" variant="micro" />
                <span class="text-sm font-medium">Referral Code</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="text-xl font-mono font-bold tracking-wider text-primary-600 uppercase">{{ $customer->referral_code }}</div>
                <flux:button size="xs" variant="ghost" icon="document-duplicate" x-on:click="window.navigator.clipboard.writeText('{{ $customer->referral_code }}'); $flux.toast('Code copied!')" />
            </div>
            <div class="text-xs text-zinc-500">{{ $customer->referrals_count ?? 0 }} Total Referrals</div>
        </flux:card>
    </div>

    <flux:tabs variant="pills">
        <flux:tab :wire:click="'setTab(\'transactions\')'" :current="$tab === 'transactions'">Transaction History</flux:tab>
        <flux:tab :wire:click="'setTab(\'referrals\')'" :current="$tab === 'referrals'">
            Referrals
            <flux:badge size="sm" inset="top" class="ml-2">{{ $customer->referrals_count ?? 0 }}</flux:badge>
        </flux:tab>
    </flux:tabs>

    @if($tab === 'transactions')
        <flux:card>
            <flux:heading level="2" class="mb-4">Recent Transactions</flux:heading>
            
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
                                <flux:badge size="sm" :color="$tx->type === 'earn' ? 'green' : ($tx->type === 'redeem' ? 'amber' : 'zinc')">
                                    {{ ucfirst($tx->type) }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="text-zinc-900 dark:text-zinc-100 font-medium">{{ $tx->note ?? 'Point Transaction' }}</flux:table.cell>
                            <flux:table.cell class="font-mono font-medium {{ $tx->points > 0 ? 'text-green-600' : 'text-amber-600' }}">
                                {{ $tx->points > 0 ? '+' : '' }}{{ number_format($tx->points) }}
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

            <flux:pagination :paginator="$transactions" />
        </flux:card>
    @else
        <flux:card>
            <flux:heading level="2" class="mb-4">Referral Activity</flux:heading>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Friend</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column>Invited On</flux:table.column>
                    <flux:table.column>Qualified On</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($referrals as $ref)
                        <flux:table.row>
                            <flux:table.cell class="font-medium">
                                {{ $ref->referred->name ?? 'Unknown' }}
                                <div class="text-xs text-zinc-500">{{ $ref->referred->phone ?? '' }}</div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$ref->status === 'credited' ? 'green' : ($ref->status === 'qualified' ? 'green' : 'zinc')">
                                    {{ ucfirst($ref->status) }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="text-xs text-zinc-500">{{ $ref->created_at->format('M j, Y') }}</flux:table.cell>
                            <flux:table.cell class="text-xs text-zinc-500">{{ $ref->qualified_at ? $ref->qualified_at->format('M j, Y') : 'Pending visit' }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="4" class="text-center py-6 text-zinc-500">
                                No referrals yet. Give them their code to start growing!
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            <flux:pagination :paginator="$referrals" />
        </flux:card>
    @endif

    <livewire:merchant.customers.award-points-form :customer="$customer" />
    <livewire:merchant.customers.redeem-reward-form :customer="$customer" />
</div>
