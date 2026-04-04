<div>
    <flux:modal name="award-points-modal" class="w-full max-w-sm">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">Award Points</flux:heading>
                <flux:subheading>Enter the transaction amount to calculate and award points for {{ $customer->name }}.</flux:subheading>
            </div>

            <div class="space-y-4">
                <flux:input 
                    wire:model="amount_spent_kes" 
                    type="number" 
                    label="Amount Spent (KES)" 
                    placeholder="0.00" 
                    step="0.01" 
                    min="0" 
                    icon-leading="banknotes"
                />
                
                <flux:textarea 
                    wire:model="note" 
                    label="Internal Note (Optional)" 
                    placeholder="Receipt # or purchase details..." 
                    rows="2"
                />
            </div>

            <div class="flex gap-3 pt-4 border-t border-zinc-100 dark:border-zinc-800">
                <flux:modal.close>
                    <flux:button variant="ghost" class="flex-1">Cancel</flux:button>
                </flux:modal.close>
                
                <flux:button 
                    type="submit" 
                    variant="primary" 
                    class="flex-1"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="save">Award Points</span>
                    <span wire:loading wire:target="save" class="flex items-center gap-2">
                        <flux:icon icon="arrow-path" class="animate-spin" />
                        Processing...
                    </span>
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
