<div>
    <flux:card class="space-y-4">
        <flux:heading size="lg">Recent Campaigns</flux:heading>

        <flux:table :paginate="$campaigns">
            <flux:table.columns>
                <flux:table.column>Name</flux:table.column>
                <flux:table.column>Segment</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Recipients (Sent/Total)</flux:table.column>
                <flux:table.column>Created At</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($campaigns as $campaign)
                    <flux:table.row :key="$campaign->id">
                        <flux:table.cell class="font-medium">
                            <flux:text color="dark">{{ $campaign->name }}</flux:text>
                            <flux:text size="xs" color="gray">{{ Str::limit($campaign->message, 50) }}</flux:text>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" variant="outline">{{ ucfirst($campaign->segment_type) }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            @php
                                $color = match($campaign->status) {
                                    'queued' => 'zinc',
                                    'processing' => 'orange',
                                    'completed' => 'green',
                                    'failed' => 'red',
                                    default => 'zinc'
                                };
                            @endphp
                            <div class="flex flex-col gap-1">
                                <flux:badge :color="$color" size="sm" inset="none">{{ ucfirst($campaign->status) }}</flux:badge>
                                @if($campaign->status === 'processing')
                                    <flux:text size="xs" class="animate-pulse">Dispatching...</flux:text>
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex flex-col gap-1.5 w-full max-w-[200px]">
                                <div class="flex justify-between text-xs font-medium">
                                    <span>{{ number_format($campaign->recipients_sent + $campaign->recipients_failed) }} / {{ number_format($campaign->recipients_total) }}</span>
                                    @if($campaign->recipients_total > 0)
                                        <span>{{ round((($campaign->recipients_sent + $campaign->recipients_failed) / $campaign->recipients_total) * 100) }}%</span>
                                    @endif
                                </div>
                                <div class="w-full h-1.5 bg-zinc-100 dark:bg-zinc-800 rounded-full overflow-hidden">
                                    <div 
                                        class="h-full bg-{{ $color }}-500 transition-all duration-500" 
                                        style="width: {{ $campaign->recipients_total > 0 ? (($campaign->recipients_sent + $campaign->recipients_failed) / $campaign->recipients_total) * 100 : 0 }}%"
                                    ></div>
                                </div>
                                @if($campaign->recipients_failed > 0)
                                    <flux:text size="xs" color="red" class="font-medium">
                                        {{ number_format($campaign->recipients_failed) }} failed
                                    </flux:text>
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell class="text-zinc-500 tabular-nums">
                            {{ $campaign->created_at->format('M d, H:i') }}
                        </flux:table.cell>
                        <flux:table.cell align="right">
                            @if($campaign->status === 'completed')
                                <flux:button variant="ghost" icon="chart-bar" wire:click="showInsights({{ $campaign->id }})" size="sm">Insights</flux:button>
                            @endif
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <!-- Insights Modal -->
    <flux:modal name="campaign-insights" class="min-w-[400px]">
        <div class="space-y-6">
            @if($selectedCampaign && $insights)
                <div>
                    <flux:heading size="lg">Campaign Insights: {{ $selectedCampaign->name }}</flux:heading>
                    <flux:subheading>Performance metrics for the first 7 days post-campaign.</flux:subheading>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:card class="space-y-1 bg-zinc-50">
                        <flux:text size="xs" class="uppercase font-semibold text-zinc-500">Recipients</flux:text>
                        <flux:heading size="lg">{{ number_format($insights['total_recipients']) }}</flux:heading>
                    </flux:card>
                    
                    <flux:card class="space-y-1 bg-zinc-50">
                        <flux:text size="xs" class="uppercase font-semibold text-zinc-500">Converted Visitors</flux:text>
                        <flux:heading size="lg">{{ number_format($insights['converted_visitors']) }}</flux:heading>
                    </flux:card>
                </div>

                <flux:card class="flex flex-col items-center justify-center p-6 space-y-2 bg-primary-50 border-primary-100">
                    <flux:text size="sm" class="font-medium text-primary-700">Conversion Rate</flux:text>
                    <div class="text-4xl font-bold text-primary-900">{{ $insights['conversion_rate'] }}%</div>
                    <flux:text size="xs" class="text-primary-600 text-center">Percentage of recipients who visited or earned points within 7 days of receiving this campaign.</flux:text>
                </flux:card>

                <div class="flex justify-end pt-4">
                    <flux:modal.close>
                        <flux:button variant="ghost">Close</flux:button>
                    </flux:modal.close>
                </div>
            @else
                <div class="flex items-center justify-center h-48">
                    <flux:text color="gray">Loading insights...</flux:text>
                </div>
            @endif
        </div>
    </flux:modal>
</div>
