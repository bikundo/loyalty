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
                        <flux:table.cell colspan="5" class="text-center py-8 text-zinc-500">
                            No customers found. Click "Enrol Member" to add one.
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
