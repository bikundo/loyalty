<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Analytics & Insights</flux:heading>
            <flux:subheading>Track your loyalty programme performance and ROI.</flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            <flux:select wire:model.live="days" class="w-48">
                <flux:select.option value="7">Last 7 days</flux:select.option>
                <flux:select.option value="30">Last 30 days</flux:select.option>
                <flux:select.option value="90">Last 90 days</flux:select.option>
            </flux:select>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <flux:card class="space-y-2">
            <flux:text size="sm" class="uppercase tracking-wider font-semibold text-zinc-500">Active Customers</flux:text>
            <div class="flex items-baseline gap-2">
                <flux:heading size="xl">{{ number_format($stats['active_customers']) }}</flux:heading>
                <flux:text size="xs" class="text-green-600 font-medium">90-day active</flux:text>
            </div>
        </flux:card>

        <flux:card class="space-y-2">
            <flux:text size="sm" class="uppercase tracking-wider font-semibold text-zinc-500">Points Liability</flux:text>
            <div class="flex items-baseline gap-2">
                <flux:heading size="xl">{{ number_format($stats['points_liability']) }}</flux:heading>
                <flux:text size="xs" class="text-zinc-500">Total outstanding</flux:text>
            </div>
        </flux:card>

        <flux:card class="space-y-2">
            <flux:text size="sm" class="uppercase tracking-wider font-semibold text-zinc-500">New Enrolments</flux:text>
            <div class="flex items-baseline gap-2">
                <flux:heading size="xl">{{ number_format($stats['new_enrolments']) }}</flux:heading>
                <flux:text size="xs" class="text-zinc-500">Last {{ $days }} days</flux:text>
            </div>
        </flux:card>

        <flux:card class="space-y-2">
            <flux:text size="sm" class="uppercase tracking-wider font-semibold text-zinc-500">Revenue Influenced</flux:text>
            <div class="flex items-baseline gap-2">
                <flux:heading size="xl">KES {{ number_format($stats['revenue_influenced']) }}</flux:heading>
                <flux:text size="xs" class="text-zinc-500">Last {{ $days }} days</flux:text>
            </div>
        </flux:card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Enrollment Trend -->
        <flux:card class="space-y-4">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Customer Growth</flux:heading>
            </div>
            
            <div class="h-64">
                <flux:chart :value="$enrollmentTrend" class="h-full">
                    <flux:chart.viewport>
                        <flux:chart.area field="value" class="text-primary-500" />
                        <flux:chart.axis.x field="date" />
                        <flux:chart.axis.y />
                        <flux:chart.tooltip />
                    </flux:chart.viewport>
                </flux:chart>
            </div>
        </flux:card>

        <!-- Points Flow -->
        <flux:card class="space-y-4">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Points Circulation</flux:heading>
            </div>
            
            <div class="h-64">
                <flux:chart :value="$pointFlow['earned']" class="h-full">
                    <flux:chart.viewport>
                        <flux:chart.line field="value" class="text-indigo-500" />
                        <!-- We would need a multi-series chart here if possible, but for now showing Earned -->
                        <flux:chart.axis.x field="date" />
                        <flux:chart.axis.y />
                        <flux:chart.tooltip />
                    </flux:chart.viewport>
                </flux:chart>
            </div>
            <flux:text size="xs" class="text-center font-medium text-zinc-500">Daily points awarded</flux:text>
        </flux:card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Customers -->
        <flux:card class="space-y-4">
            <flux:heading size="lg">Top Customers</flux:heading>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Customer</flux:table.column>
                    <flux:table.column>Visits</flux:table.column>
                    <flux:table.column align="right">Balance</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($topCustomers as $customer)
                        <flux:table.row :key="$customer->id">
                            <flux:table.cell>
                                <div class="font-medium text-zinc-900">{{ $customer->name }}</div>
                                <div class="text-xs text-zinc-500">{{ $customer->phone }}</div>
                            </flux:table.cell>
                            <flux:table.cell>{{ $customer->total_visits }}</flux:table.cell>
                            <flux:table.cell align="right">
                                <flux:badge color="zinc" inset="top bottom">{{ number_format($customer->total_points) }} pts</flux:badge>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>

        <!-- Popular Rewards -->
        <flux:card class="space-y-4">
            <flux:heading size="lg">Popular Rewards</flux:heading>
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Reward</flux:table.column>
                    <flux:table.column align="right">Redemptions</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($popularRewards as $redemption)
                        <flux:table.row :key="$redemption->reward_id">
                            <flux:table.cell>{{ $redemption->reward->name }}</flux:table.cell>
                            <flux:table.cell align="right">
                                <div class="font-bold">{{ $redemption->redemptions_count }}</div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>
    </div>
</div>
