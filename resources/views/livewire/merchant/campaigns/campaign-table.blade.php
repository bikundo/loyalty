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
                                    'pending' => 'blue',
                                    'processing' => 'orange',
                                    'completed' => 'green',
                                    'failed' => 'red',
                                    default => 'zinc'
                                };
                            @endphp
                            <flux:badge :color="$color" size="sm" inset="none">{{ ucfirst($campaign->status) }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ number_format($campaign->recipients_sent) }} / {{ number_format($campaign->recipients_total) }}
                        </flux:table.cell>
                        <flux:table.cell class="text-zinc-500 tabular-nums">
                            {{ $campaign->created_at->format('M d, H:i') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
