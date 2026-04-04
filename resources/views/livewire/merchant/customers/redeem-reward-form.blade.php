<flux:modal name="redeem-reward-modal" class="w-full max-w-lg">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Redeem Reward</flux:heading>
            <flux:subheading>Choose a reward to redeem for {{ $customer->name }}.</flux:subheading>
        </div>

        <div class="space-y-4 max-h-[60vh] overflow-y-auto pr-2">
            @forelse ($rewards as $reward)
                <div class="flex items-center justify-between p-4 rounded-xl border border-zinc-100 dark:border-zinc-800 {{ $customer->total_points < $reward->points_required ? 'opacity-50 grayscale select-none cursor-not-allowed' : 'hover:bg-zinc-50 dark:hover:bg-zinc-900 transition-all cursor-pointer group' }}"
                    @if ($customer->total_points >= $reward->points_required)
                        wire:click="redeem('{{ $reward->id }}')"
                    @endif
                >
                    <div class="flex items-center gap-3">
                        <div class="p-2 rounded-lg bg-amber-50 dark:bg-amber-950 text-amber-600 dark:text-amber-400">
                            <flux:icon icon="gift" variant="outline" size="sm" />
                        </div>
                        <div>
                            <flux:heading size="sm">{{ $reward->name }}</flux:heading>
                            <div class="text-xs text-zinc-500">
                                @if ($reward->type === 'discount_fixed')
                                    KES {{ number_format($reward->discount_value_kes) }} discount
                                @elseif ($reward->type === 'discount_percent')
                                    {{ $reward->discount_percentage }}% discount
                                @else
                                    Free item
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="text-right flex flex-col items-end gap-1">
                        <div class="text-sm font-semibold {{ $customer->total_points < $reward->points_required ? 'text-zinc-400' : 'text-amber-600' }}">
                            {{ number_format($reward->points_required) }} pts
                        </div>
                        @if ($customer->total_points < $reward->points_required)
                            <div class="text-[10px] uppercase font-bold text-zinc-400">Locked</div>
                        @else
                            <div class="text-[10px] uppercase font-bold text-green-500 group-hover:block hidden">Click to Redeem</div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-8 text-zinc-500 italic">
                    No active rewards found for this merchant.
                </div>
            @endforelse
        </div>

        <div class="flex justify-end pt-4 border-t border-zinc-100 dark:border-zinc-800">
            <flux:button variant="ghost" x-on:click="$dispatch('close-modal', { name: 'redeem-reward-modal' })">Cancel</flux:button>
        </div>
    </div>
</flux:modal>