<div>
    <div class="flex justify-between items-end mb-6">
        <div>
            <flux:heading size="xl" level="1">Customers</flux:heading>
            <flux:subheading>Manage your loyalty program members.</flux:subheading>
        </div>
        
        <flux:modal.trigger name="enrol-customer">
            <flux:button variant="primary" icon="plus">Enrol Member</flux:button>
        </flux:modal.trigger>
    </div>

    <div class="flex flex-col md:flex-row gap-4 mb-6">
        <div class="flex-1 relative group">
            <flux:input 
                wire:model.live.debounce.300ms="search" 
                placeholder="Search by name or phone..." 
                icon="magnifying-glass"
                clearable
                x-on:keydown.window.prevent.slash="$el.querySelector('input').focus()"
                kbd="/"
            />
            <div wire:loading wire:target="search" class="absolute right-10 top-2.5">
                <flux:icon icon="loading" class="animate-spin text-zinc-400" />
            </div>
        </div>

        <div class="w-full md:w-48">
            <flux:select wire:model.live="status" placeholder="All Statuses">
                <flux:select.option value="">All Statuses</flux:select.option>
                <flux:select.option value="active">Active</flux:select.option>
                <flux:select.option value="inactive">Inactive</flux:select.option>
                <flux:select.option value="suspended">Suspended</flux:select.option>
            </flux:select>
        </div>
    </div>

    <div class="space-y-4">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Name</flux:table.column>
                <flux:table.column>Phone</flux:table.column>
                <flux:table.column>Enrolled</flux:table.column>
                <flux:table.column>Points Balance</flux:table.column>
                <flux:table.column>Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($customers as $customer)
                    <flux:table.row>
                        <flux:table.cell>
                            <div class="font-medium">{{ $customer->name }}</div>
                            <div class="text-xs text-zinc-500">{{ $customer->status ?? 'Active' }}</div>
                        </flux:table.cell>
                        <flux:table.cell class="font-mono text-sm">{{ $customer->phone }}</flux:table.cell>
                        <flux:table.cell class="text-sm text-zinc-500">
                            {{ $customer->enrolled_at ? $customer->enrolled_at->format('M j, Y') : $customer->created_at->format('M j, Y') }}
                        </flux:table.cell>
                        <flux:table.cell class="font-mono font-semibold text-primary-600">
                            {{ number_format($customer->total_points) }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:button variant="ghost" size="sm" icon="eye" href="{{ route('admin.customers.show', $customer) }}" />
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center py-12">
                            <div class="flex flex-col items-center justify-center space-y-4">
                                <flux:icon icon="magnifying-glass" class="w-12 h-12 text-zinc-200" />
                                <div class="space-y-1">
                                    <flux:heading>No customers found</flux:heading>
                                    @if ($search)
                                        <flux:subheading>We couldn't find any customers matching "{{ $search }}"</flux:subheading>
                                    @else
                                        <flux:subheading>Click "Enrol Member" to add your first customer.</flux:subheading>
                                    @endif
                                </div>
                                @if ($search || $status)
                                    <flux:button variant="ghost" size="sm" wire:click="$set('search', ''); $set('status', '')">Clear Filters</flux:button>
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <div>
            <flux:pagination :paginator="$customers" />
        </div>
    </div>

    {{-- Enrolment Modal --}}
    <flux:modal name="enrol-customer" class="md:w-96">
        <div class="space-y-2 mb-4">
            <flux:heading size="lg">Enrol Member</flux:heading>
            <flux:subheading>Add a new customer to your loyalty program.</flux:subheading>
        </div>
        
        <livewire:merchant.customers.enrolment-form />
    </flux:modal>
</div>
