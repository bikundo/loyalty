@php
    $renderSparkline = function($data, $color = 'stroke-primary-500') {
        $max = max(collect($data)->max() ?: 1, 1);
        $count = count($data);
        $width = 100;
        $height = 30;
        $points = collect(array_values($data))
            ->map(fn($v, $i) => ($i * ($width / ($count - 1))) . ',' . ($height - ($v / $max * $height)))
            ->implode(' ');
        
        return sprintf(
            '<svg viewBox="0 0 %d %d" class="w-24 h-8 %s"><polyline fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" points="%s" /></svg>',
            $width, $height, $color, $points
        );
    };
@endphp

<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">Overview</flux:heading>
            <flux:subheading>Performance metrics for {{ $tenantName }}</flux:subheading>
        </div>
        
        <div class="flex gap-2">
            <flux:button icon="arrow-path" variant="ghost" wire:click="$refresh">Refresh</flux:button>
            <flux:button variant="primary" icon="plus" href="/admin/customers">Award Points</flux:button>
        </div>
    </div>

    {{-- Core Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <flux:card class="relative overflow-hidden group">
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-zinc-500 dark:text-zinc-400">
                        <flux:icon icon="users" variant="micro" />
                        <span class="text-sm font-medium">Monthly Enrolments</span>
                    </div>
                    {!! $renderSparkline($trends['enrolments'], 'stroke-blue-500') !!}
                </div>
                <div class="text-3xl font-bold tracking-tight">{{ number_format($stats['enrolments']) }}</div>
                <div class="text-xs text-zinc-500">New members this month</div>
            </div>
        </flux:card>

        <flux:card class="relative overflow-hidden group">
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-primary-600">
                        <flux:icon icon="gift" variant="micro" />
                        <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Points Issued (MTD)</span>
                    </div>
                    {!! $renderSparkline($trends['pointFlow']['earned'], 'stroke-green-500') !!}
                </div>
                <div class="text-3xl font-bold tracking-tight text-primary-600">{{ number_format($stats['points_earned']) }}</div>
                <div class="text-xs text-zinc-500">Total points awarded this month</div>
            </div>
        </flux:card>

        <flux:card class="relative overflow-hidden group">
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-zinc-500 dark:text-zinc-400">
                        <flux:icon icon="arrow-path" variant="micro" />
                        <span class="text-sm font-medium">Redemptions (MTD)</span>
                    </div>
                    {!! $renderSparkline($trends['pointFlow']['redeemed'], 'stroke-amber-500') !!}
                </div>
                <div class="text-3xl font-bold tracking-tight">{{ number_format($stats['redemptions']) }}</div>
                <div class="text-xs text-zinc-500">Successful reward redemptions</div>
            </div>
        </flux:card>
    </div>

    {{-- Recent Activity & Insights --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-4">
            <div class="flex items-center justify-between px-2">
                <flux:heading level="2">Recent Transactions</flux:heading>
                <flux:button variant="ghost" size="sm" href="/admin/customers">View all</flux:button>
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Customer</flux:table.column>
                    <flux:table.column>Action</flux:table.column>
                    <flux:table.column>Amount</flux:table.column>
                    <flux:table.column>Time</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($recentTransactions as $tx)
                        <flux:table.row>
                            <flux:table.cell>
                                <div class="flex items-center gap-3">
                                    <flux:avatar size="xs" :name="$tx->customer->name" />
                                    <div>
                                        <div class="font-medium text-sm">{{ $tx->customer->name }}</div>
                                        <div class="text-xs text-zinc-500">{{ $tx->customer->phone }}</div>
                                    </div>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$tx->type === 'earn' ? 'green' : 'amber'" variant="pill">
                                    {{ ucfirst($tx->type) }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell class="font-mono font-medium {{ $tx->points > 0 ? 'text-green-600' : 'text-amber-600' }}">
                                {{ $tx->points > 0 ? '+' : '' }}{{ number_format($tx->points) }}
                            </flux:table.cell>
                            <flux:table.cell class="text-xs text-zinc-500">{{ $tx->created_at->diffForHumans() }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="4" class="text-center py-8 text-zinc-500 italic">
                                No recent activity found.
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>

        <div class="space-y-6">
            <flux:heading level="2" class="px-2">Loyalty Health</flux:heading>
            
            <flux:card class="space-y-4">
                <div class="space-y-1">
                    <div class="flex justify-between text-sm font-medium">
                        <span>Active Members</span>
                        <span>84%</span>
                    </div>
                    <div class="h-2 w-full bg-zinc-100 dark:bg-zinc-800 rounded-full overflow-hidden">
                        <div class="h-full bg-primary-500" style="width: 84%"></div>
                    </div>
                    <p class="text-xs text-zinc-500">Customers visited in last 30 days</p>
                </div>

                <div class="space-y-1">
                    <div class="flex justify-between text-sm font-medium">
                        <span>Redemption Rate</span>
                        <span>12%</span>
                    </div>
                    <div class="h-2 w-full bg-zinc-100 dark:bg-zinc-800 rounded-full overflow-hidden">
                        <div class="h-full bg-green-500" style="width: 12%"></div>
                    </div>
                    <p class="text-xs text-zinc-500">Points redeemed vs Points issued</p>
                </div>

                <flux:spacer />

                <div class="pt-4 border-t border-zinc-200 dark:border-zinc-800">
                    <flux:button variant="subtle" class="w-full" icon="presentation-chart-line">View Reports</flux:button>
                </div>
            </flux:card>
        </div>
    </div>
</div>
