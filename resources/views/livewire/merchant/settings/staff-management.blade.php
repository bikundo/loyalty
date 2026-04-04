<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">Staff & Locations</flux:heading>
            <flux:subheading>Manage cashier access, PINs, and daily award limits.</flux:subheading>
        </div>
        
        <flux:button variant="primary" icon="plus" wire:click="createStaff">Add Staff</flux:button>
    </div>

    <flux:card class="space-y-4">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Name</flux:table.column>
                <flux:table.column>Location</flux:table.column>
                <flux:table.column>Daily Cap</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Action</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($staff as $member)
                    <flux:table.row>
                        <flux:table.cell class="font-medium">{{ $member->name }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" variant="pill">{{ $member->location?->name ?? 'Global' }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="font-mono text-sm">
                            KES {{ number_format($member->daily_award_cap_kes) }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$member->is_active ? 'green' : 'zinc'">
                                {{ $member->is_active ? 'Active' : 'Inactive' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button variant="ghost" size="sm" icon="pencil" wire:click="editStaff('{{ $member->id }}')" />
                                <flux:button variant="ghost" size="sm" icon="no-symbol" wire:click="toggleStatus('{{ $member->id }}')" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center py-8 text-zinc-500 italic">
                            No staff members found.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{-- Staff Creation/Editing Modal --}}
    <flux:modal :show="$showStaffModal" wire:model="showStaffModal" class="w-full max-w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $staffId ? 'Edit Staff Member' : 'Add New Staff Member' }}</flux:heading>
                <flux:subheading>Define access for the merchant scanner app.</flux:subheading>
            </div>

            <div class="space-y-4">
                <flux:input label="Full Name" wire:model="name" placeholder="John Doe" />

                <div class="grid grid-cols-2 gap-4">
                    <flux:input label="Login PIN" type="password" wire:model="pin" placeholder="{{ $staffId ? 'Leave blank to keep' : '4-8 digits' }}" />
                    
                    <flux:select label="Primary Location" wire:model="locationId" placeholder="Select Location">
                        <option value="">Global / Central</option>
                        @foreach ($locations as $loc)
                            <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:input label="Daily Award Cap (KES)" type="number" wire:model="dailyCap" hint="Maximum spend value this cashier can award in points per day." />

                <flux:switch label="Account Active" wire:model="isActive" />
            </div>

            <div class="flex gap-2 justify-end">
                <flux:button variant="ghost" wire:click="$set('showStaffModal', false)">Cancel</flux:button>
                <flux:button variant="primary" wire:click="saveStaff">Save Member</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
