<div>
    <flux:modal name="redeem-reward-modal" class="md:w-[500px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Redeem Reward</flux:heading>
                <flux:subheading>Select a reward for {{ $customer->name ?? 'this customer' }} to redeem using their points.</flux:subheading>
            </div>

            <div class="space-y-4">
                <div class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg border border-zinc-200 dark:border-zinc-700">
                    <div class="text-sm font-medium text-zinc-500">Available Balance</div>
                    <div class="text-lg font-bold text-primary-600">{{ number_format($customer->total_points) }} Points</div>
                </div>

                <div class="space-y-2">
                    <flux:label>Select Reward</flux:label>
                    <div class="grid grid-cols-1 gap-2 max-h-[300px] overflow-y-auto p-1">
                        @forelse ($rewards as $reward)
                            @php
                                $canAfford = $customer->total_points >= $reward->points_required;
                            @endphp
                            <label class="relative flex items-center p-4 border rounded-xl cursor-pointer transition-all hover:bg-zinc-50 dark:hover:bg-zinc-800/50 {{ $selectedRewardId == $reward->id ? 'border-primary-500 bg-primary-50/10 ring-1 ring-primary-500' : 'border-zinc-200 dark:border-zinc-700' }} {{ !$canAfford ? 'opacity-60 grayscale cursor-not-allowed bg-zinc-50/50' : '' }}">
                                <input type="radio" wire:model.live="selectedRewardId" value="{{ $reward->id }}" class="hidden" {{ !$canAfford ? 'disabled' : '' }}>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-zinc-900 dark:text-zinc-100">{{ $reward->name }}</span>
                                        <span class="text-sm font-mono {{ $canAfford ? 'text-primary-600' : 'text-zinc-500' }}">{{ number_format($reward->points_required) }} pts</span>
                                    </div>
                                    @if ($reward->description)
                                        <p class="text-xs text-zinc-500 mt-1">{{ $reward->description }}</p>
                                    @endif
                                    @if (!$canAfford)
                                        <div class="mt-2 flex items-center gap-1 text-[10px] text-amber-600 font-medium uppercase tracking-wider">
                                            <flux:icon icon="exclamation-triangle" variant="micro" />
                                            Needs {{ number_format($reward->points_required - $customer->total_points) }} more points
                                        </div>
                                    @endif
                                </div>
                                @if ($canAfford && $selectedRewardId == $reward->id)
                                    <flux:icon icon="check-circle" variant="solid" class="text-primary-500 ml-3" />
                                @endif
                            </label>
                        @empty
                            <div class="text-center py-8 border-2 border-dashed border-zinc-200 dark:border-zinc-700 rounded-xl">
                                <flux:icon icon="gift" class="mx-auto h-8 w-8 text-zinc-300" />
                                <p class="text-sm text-zinc-400 mt-2">No active rewards available.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="flex justify-end space-x-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                
                <flux:button 
                    wire:click="redeem" 
                    variant="primary" 
                    :disabled="!$selectedRewardId"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="redeem">Confirm Redemption</span>
                    <span wire:loading wire:target="redeem" class="flex items-center gap-2">
                        <flux:icon icon="loading" class="animate-spin" />
                        Processing...
                    </span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>