<form wire:submit="save" class="space-y-6">
    <flux:input wire:model="name" label="Full Name" placeholder="e.g. John Doe" required />

    <flux:input wire:model="phone" label="Phone Number" placeholder="e.g. 0712345678" type="tel" required />

    <flux:input wire:model="date_of_birth" label="Date of Birth (Optional)" type="date" />
    
    <flux:input wire:model="referral_code" label="Referral Code (Optional)" placeholder="e.g. BETH-1234" />

    <div class="flex justify-end gap-2 pt-4">
        <flux:modal.close>
            <flux:button variant="ghost">Cancel</flux:button>
        </flux:modal.close>
        
        <flux:button type="submit" variant="primary">Save Member</flux:button>
    </div>
</form>
