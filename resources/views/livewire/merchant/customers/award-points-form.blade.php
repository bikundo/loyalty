<div>
    <flux:modal name="award-points-modal" class="md:w-96">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">Award Points</flux:heading>
                <flux:subheading>Award points to {{ $customer->name ?? 'Customer' }} based on their purchase.</flux:subheading>
            </div>

            <flux:input wire:model="amount_spent_kes" type="number" label="Amount Spent (KES)" placeholder="0.00" step="0.01" min="0" />
            
            <flux:input wire:model="note" label="Internal Note (Optional)" placeholder="Receipt # or reason" />

            <div class="flex justify-end space-x-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancel</flux:button>
                </flux:modal.close>
                
                <flux:button type="submit" variant="primary">Award Points</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
