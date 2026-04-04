<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">Reward Catalog</flux:heading>
            <flux:subheading>Manage the rewards customers can redeem with their loyalty points.</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="createReward">Create Reward</flux:button>
    </div>

    <flux:card>
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Reward</flux:table.column>
                <flux:table.column>Type</flux:table.column>
                <flux:table.column>Points</flux:table.column>
                <flux:table.column>Redemptions</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($rewards as $reward)
                    <flux:table.row class="cursor-pointer" wire:click="editReward('{{ $reward->id }}')">
                        <flux:table.cell>
                            <div class="font-medium">{{ $reward->name }}</div>
                            <div class="text-xs text-zinc-500 line-clamp-1">{{ $reward->description ?? '—' }}</div>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($reward->type === 'discount_fixed')
                                <flux:badge size="sm" variant="pill">KES {{ number_format($reward->discount_value_kes) }} off</flux:badge>
                            @elseif ($reward->type === 'discount_percent')
                                <flux:badge size="sm" variant="pill">{{ $reward->discount_percentage }}% off</flux:badge>
                            @else
                                <flux:badge size="sm" variant="pill">Free item</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="font-mono text-sm">
                            {{ number_format($reward->points_required) }}
                        </flux:table.cell>
                        <flux:table.cell class="font-mono text-sm">
                            {{ number_format($reward->redemptions_count) }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$reward->is_active ? 'green' : 'zinc'">
                                {{ $reward->is_active ? 'Active' : 'Inactive' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div x-on:click.stop>
                                <flux:dropdown position="bottom" align="end">
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" icon:variant="mini" />

                                    <flux:menu>
                                        <flux:menu.item icon="pencil-square" wire:click="editReward('{{ $reward->id }}')">Edit</flux:menu.item>
                                        <flux:menu.item icon="{{ $reward->is_active ? 'no-symbol' : 'check-circle' }}" wire:click="toggleStatus('{{ $reward->id }}')">
                                            {{ $reward->is_active ? 'Deactivate' : 'Activate' }}
                                        </flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:modal.trigger name="delete-reward-{{ $reward->id }}">
                                            <flux:menu.item icon="trash" variant="danger">Delete</flux:menu.item>
                                        </flux:modal.trigger>
                                    </flux:menu>
                                </flux:dropdown>
                            </div>

                            {{-- Delete Confirmation --}}
                            <flux:modal :name="'delete-reward-' . $reward->id" class="w-full max-w-sm">
                                <div class="space-y-6">
                                    <div>
                                        <flux:heading size="lg">Delete Reward?</flux:heading>
                                        <flux:subheading>This will permanently remove <strong>{{ $reward->name }}</strong> from your catalog.</flux:subheading>
                                    </div>
                                    <div class="flex gap-2 justify-end">
                                        <flux:button variant="ghost" x-on:click="$dispatch('close-modal', { name: 'delete-reward-{{ $reward->id }}' })">Cancel</flux:button>
                                        <flux:button variant="danger" wire:click="deleteReward('{{ $reward->id }}')">Delete</flux:button>
                                    </div>
                                </div>
                            </flux:modal>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="text-center py-12">
                            <div class="flex flex-col items-center justify-center space-y-4">
                                <flux:icon icon="gift" class="w-12 h-12 text-zinc-200" />
                                <div class="space-y-1">
                                    <flux:heading>No rewards yet</flux:heading>
                                    <flux:subheading>Create your first reward to start building your catalog.</flux:subheading>
                                </div>
                                <flux:button variant="primary" size="sm" icon="plus" wire:click="createReward">Create Reward</flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{-- Reward Creation/Editing Modal --}}
    <flux:modal :show="$showRewardModal" wire:model="showRewardModal" class="w-full max-w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $rewardId ? 'Edit Reward' : 'Add New Reward' }}</flux:heading>
                <flux:subheading>Define the reward and its point cost.</flux:subheading>
            </div>

            <div class="space-y-4">
                <flux:input label="Reward Name" wire:model="name" placeholder="Free Cappuccino" />
                <flux:textarea label="Description" wire:model="description" placeholder="A premium medium-sized cappuccino of your choice." />

                <div class="grid grid-cols-2 gap-4">
                    <flux:select label="Reward Type" wire:model.live="type">
                        <option value="item">Free Item/Service</option>
                        <option value="discount_fixed">Fixed Cash Discount</option>
                        <option value="discount_percent">Percentage Discount</option>
                    </flux:select>
                    
                    <flux:input label="Points Required" type="number" wire:model="pointsRequired" hint="Cost in loyalty points" />
                </div>

                @if ($type === 'discount_fixed')
                    <flux:input label="Discount Value (KES)" type="number" wire:model="discountValueKes" placeholder="500" />
                @endif

                @if ($type === 'discount_percent')
                    <flux:input label="Discount Percentage (%)" type="number" wire:model="discountPercentage" placeholder="10" />
                @endif

                <flux:switch label="Active & Redeemable" wire:model="isActive" />
            </div>

            <div class="flex gap-2 justify-end">
                <flux:button variant="ghost" wire:click="$set('showRewardModal', false)">Cancel</flux:button>
                <flux:button variant="primary" wire:click="saveReward">Save Reward</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
