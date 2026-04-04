<div>
    <flux:modal name="redeem-reward-modal" persistent class="w-full max-w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Redeem Reward</flux:heading>
                <flux:subheading>Choose an available reward for {{ $customer->name }}.</flux:subheading>
            </div>

            <div class="space-y-4">
                <div class="flex items-center justify-between p-4 rounded-xl bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-700">
                    <div class="space-y-1">
                        <div class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Current Balance</div>
                        <div class="text-xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($customer->total_points) }} Points</div>
                    </div>
                    <flux:icon icon="star" variant="solid" class="text-primary-500 w-8 h-8 opacity-20" />
                </div>

                <div class="space-y-2">
                    <flux:label>Select Available Reward</flux:label>
                    <div class="grid grid-cols-1 gap-2 max-h-[350px] overflow-y-auto pr-1">
                        @forelse ($rewards as $reward)
                            @php
                                $canAfford = $customer->total_points >= $reward->points_required;
                            @endphp
                            <label class="relative flex items-center p-4 rounded-xl border transition-all cursor-pointer @if($selectedRewardId == $reward->id) border-primary-500 bg-primary-50/10 ring-1 ring-primary-500 @else border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 @endif @if(!$canAfford) opacity-60 cursor-not-allowed grayscale @endif">
                                <input 
                                    type="radio" 
                                    wire:model.live="selectedRewardId" 
                                    value="{{ $reward->id }}" 
                                    class="hidden" 
                                    @disabled(!$canAfford)
                                >
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold text-zinc-900 dark:text-zinc-100">{{ $reward->name }}</span>
                                        <div class="text-xs font-mono px-2 py-1 bg-zinc-100 dark:bg-zinc-800 rounded @if($canAfford) text-primary-600 @else text-zinc-500 @endif">
                                            {{ number_format($reward->points_required) }} pts
                                        </div>
                                    </div>
                                    @if($reward->description)
                                        <p class="text-xs text-zinc-500 mt-1 line-clamp-2">{{ $reward->description }}</p>
                                    @endif
                                    @if(!$canAfford)
                                        <div class="mt-2 flex items-center gap-1.5 text-[10px] font-bold text-amber-600 uppercase">
                                            <flux:icon icon="exclamation-circle" variant="micro" />
                                            Requires {{ number_format($reward->points_required - $customer->total_points) }} more points
                                        </div>
                                    @endif
                                </div>
                                
                                @if($selectedRewardId == $reward->id)
                                    <flux:icon icon="check-circle" variant="solid" class="ml-4 text-primary-500" />
                                @endif
                            </label>
                        @empty
                            <div class="flex flex-col items-center justify-center p-8 border-2 border-dashed border-zinc-200 dark:border-zinc-700 rounded-xl bg-zinc-50/50 dark:bg-zinc-900/10">
                                <flux:icon icon="gift" class="text-zinc-300 w-12 h-12 mb-3" />
                                <div class="text-sm font-medium text-zinc-500">No active rewards available</div>
                                <div class="text-xs text-zinc-400 mt-1 text-center">Please configure rewards in the settings.</div>
                            </div>
                        @endforelse
                    </div>
                </div>

                @error('selectedRewardId')
                    <p class="text-sm text-red-500 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-3 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                <flux:modal.close>
                    <flux:button variant="ghost" class="flex-1">Cancel</flux:button>
                </flux:modal.close>

                <flux:button 
                    wire:click="redeem" 
                    variant="primary" 
                    class="flex-1"
                    :disabled="!$selectedRewardId"
                >
                    <span wire:loading.remove wire:target="redeem">Redeem Now</span>
                    <span wire:loading wire:target="redeem" class="flex items-center gap-2">
                        <flux:icon icon="arrow-path" class="animate-spin" />
                        Processing...
                    </span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>