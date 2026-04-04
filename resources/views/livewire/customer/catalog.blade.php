<div class="space-y-8">
    {{-- Header with Points --}}
    <div class="flex items-center justify-between bg-amber-50 dark:bg-amber-950 p-6 rounded-2xl border border-amber-100 dark:border-amber-900 shadow-sm overflow-hidden relative group">
        <div class="absolute -right-4 -bottom-4 text-amber-500 opacity-10 group-hover:opacity-20 transition-all duration-700">
            <flux:icon icon="gift" size="xl" variant="outline" class="w-32 h-32" />
        </div>
        
        <div class="space-y-1 relative z-10">
            <flux:heading size="xl" level="1">Welcome Back!</flux:heading>
            <flux:subheading color="zinc" class="max-w-xs">You have points to spend on amazing rewards in our catalog.</flux:subheading>
        </div>
        
        <div class="text-right space-y-1 relative z-10">
            <div class="text-xs uppercase tracking-widest text-amber-600 font-bold">Total Points</div>
            <div class="text-3xl font-black text-amber-700 dark:text-amber-400">{{ number_format($points) }}</div>
        </div>
    </div>

    {{-- Reward Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse ($rewards as $reward)
            @php
                $progress = min(100, ($points / max(1, $reward->points_required)) * 100);
                $isEligible = $points >= $reward->points_required;
            @endphp
            
            <flux:card class="flex flex-col h-full hover:shadow-md transition-shadow group relative overflow-hidden">
                {{-- Points Required Badge --}}
                <div class="absolute top-4 right-4 z-10">
                    <flux:badge size="sm" color="{{ $isEligible ? 'amber' : 'zinc' }}" variant="pill">
                        {{ number_format($reward->points_required) }} pts
                    </flux:badge>
                </div>

                <div class="space-y-4 flex-grow">
                    <div>
                        <flux:heading size="lg">{{ $reward->name }}</flux:heading>
                        <flux:subheading class="line-clamp-2 min-h-[3rem]">{{ $reward->description ?? 'No description provided.' }}</flux:subheading>
                    </div>

                    {{-- Progress Bar --}}
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-xs font-medium">
                            <span class="{{ $isEligible ? 'text-amber-600' : 'text-zinc-500' }}">
                                {{ $isEligible ? 'Ready to Redeem!' : 'Points Progress' }}
                            </span>
                            <span class="text-zinc-400">{{ round($progress) }}%</span>
                        </div>
                        
                        <div class="w-full h-2 bg-zinc-100 dark:bg-zinc-800 rounded-full overflow-hidden">
                            <div class="h-full {{ $isEligible ? 'bg-amber-500' : 'bg-amber-300' }} transition-all duration-500" 
                                style="width: {{ $progress }}%">
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        @if ($reward->type === 'discount_fixed')
                            <flux:badge size="sm" variant="outline">KES {{ number_format($reward->discount_value_kes) }} OFF</flux:badge>
                        @elseif ($reward->type === 'discount_percent')
                            <flux:badge size="sm" variant="outline">{{ $reward->discount_percentage }}% OFF</flux:badge>
                        @else
                            <flux:badge size="sm" variant="outline">Free Item</flux:badge>
                        @endif
                    </div>
                </div>

                <div class="pt-6 mt-auto">
                    @if ($isEligible)
                        <flux:button variant="primary" class="w-full shadow-lg shadow-amber-200 dark:shadow-none" icon="gift">Redeem Now</flux:button>
                        <div class="text-[10px] text-center mt-2 text-zinc-400 italic">Show this reward to cashier at the shop</div>
                    @else
                        <flux:button variant="ghost" disabled class="w-full bg-zinc-100 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 opacity-50 cursor-not-allowed">
                            Need {{ number_format($reward->points_required - $points) }} more
                        </flux:button>
                    @endif
                </div>
            </flux:card>
        @empty
            <div class="col-span-full py-12 text-center border-2 border-dashed border-zinc-200 dark:border-zinc-800 rounded-xl space-y-4">
                <div class="flex justify-center flex-col items-center gap-2 text-zinc-500">
                    <flux:icon icon="gift" size="xl" variant="outline" class="opacity-20" />
                    <p class="italic">No rewards available in the catalog yet. Check back soon!</p>
                </div>
            </div>
        @endforelse
    </div>
</div>
