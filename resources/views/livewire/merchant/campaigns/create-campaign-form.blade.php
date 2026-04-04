<div>
    <flux:modal name="create-campaign-modal" class="w-full max-w-lg">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">Create SMS Campaign</flux:heading>
                <flux:subheading>Compose a message and select your target audience.</flux:subheading>
            </div>

            <div class="space-y-4">
                <flux:input 
                    wire:model="name" 
                    label="Campaign Name" 
                    placeholder="e.g., Weekend Promo - March" 
                />
                
                <flux:textarea 
                    wire:model="message" 
                    label="SMS Message" 
                    placeholder="Type your message here..." 
                    rows="3"
                    hint="One SMS is 160 characters. Long messages will use multiple credits."
                />

                <flux:radio.group wire:model.live="segment_type" label="Target Segment">
                    <flux:radio value="all" label="All Customers" />
                    <flux:radio value="active" label="Active this month (last 30 days)" />
                    <flux:radio value="lapsed" label="Lapsed (no visit in 60+ days)" />
                    <flux:radio value="high_value" label="High-Value (Top 10% by lifetime points)" />
                </flux:radio.group>

                @if ($audienceCount > 0)
                    <flux:callout variant="info" icon="information-circle">
                        <div class="flex flex-col">
                            <span>Targeting <strong>{{ number_format($audienceCount) }}</strong> customers.</span>
                            <span class="text-xs">Estimated cost: <strong>{{ number_format($this->creditsRequired) }}</strong> SMS credits.</span>
                        </div>
                    </flux:callout>
                @endif
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
                    <span wire:loading.remove wire:target="save">Send Campaign</span>
                    <span wire:loading wire:target="save" class="flex items-center gap-2">
                        <flux:icon icon="arrow-path" class="animate-spin" />
                        Processing...
                    </span>
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
