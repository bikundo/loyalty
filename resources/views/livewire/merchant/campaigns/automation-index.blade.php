<div class="space-y-6">
    <flux:card class="p-0 overflow-hidden">
        <div class="px-6 py-4 bg-zinc-50 border-b border-zinc-200">
            <flux:heading size="lg">Customer Lifecycle Automations</flux:heading>
            <flux:subheading>Set and forget engagement rules to drive loyalty.</flux:subheading>
        </div>

        <div class="divide-y divide-zinc-200">
            <!-- Birthday Automation -->
            <div class="p-6 space-y-4">
                <div class="flex items-start justify-between">
                    <div>
                        <flux:heading>Birthday Rewards</flux:heading>
                        <flux:subheading>Sent at 08:00 AM on the customer's birthday.</flux:subheading>
                    </div>
                    <flux:switch wire:model="rules.birthday.is_enabled" wire:change="saveRule('birthday')" />
                </div>

                @if($rules['birthday']['is_enabled'])
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 animate-in fade-in slide-in-from-top-4">
                        <flux:field>
                            <flux:label>Bonus Points Gift</flux:label>
                            <flux:input type="number" wire:model.blur="rules.birthday.points_bonus" wire:change="saveRule('birthday')" />
                            <flux:description>Points awarded automatically as a gift.</flux:description>
                        </flux:field>

                        <flux:field>
                            <flux:label>SMS Message Template</flux:label>
                            <flux:textarea wire:model.blur="rules.birthday.message_template" wire:change="saveRule('birthday')" rows="3" />
                            <flux:description>Use <code>{{name}}</code> and <code>{{points}}</code> as placeholders.</flux:description>
                        </flux:field>
                    </div>
                @endif
            </div>

            <!-- Milestone Nudge -->
            <div class="p-6 space-y-4">
                <div class="flex items-start justify-between">
                    <div>
                        <flux:heading>Reward Milestone Nudge</flux:heading>
                        <flux:subheading>Notify customers when they are "almost there" to a reward.</flux:subheading>
                    </div>
                    <flux:switch wire:model="rules.reward_milestone.is_enabled" wire:change="saveRule('reward_milestone')" />
                </div>

                @if($rules['reward_milestone']['is_enabled'])
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 animate-in fade-in slide-in-from-top-4">
                        <flux:field>
                            <flux:label>Trigger Threshold (%)</flux:label>
                            <flux:input type="number" step="0.05" wire:model.blur="rules.reward_milestone.config.milestone_threshold" wire:change="saveRule('reward_milestone')" />
                            <flux:description>Percentage of points needed (e.g., 0.9 for 90%).</flux:description>
                        </flux:field>

                        <flux:field>
                            <flux:label>SMS Message Template</flux:label>
                            <flux:textarea wire:model.blur="rules.reward_milestone.message_template" wire:change="saveRule('reward_milestone')" rows="3" />
                            <flux:description>Use <code>{{name}}</code>, <code>{{reward_name}}</code>, and <code>{{points_needed}}</code>.</flux:description>
                        </flux:field>
                    </div>
                @endif
            </div>

            <!-- Lapsed Customer Win-back -->
            <div class="p-6 space-y-4">
                <div class="flex items-start justify-between">
                    <div>
                        <flux:heading>Lapsed Customer Win-back</flux:heading>
                        <flux:subheading>Re-engage regulars who haven't visited in a while.</flux:subheading>
                    </div>
                    <flux:switch wire:model="rules.lapsed_customer.is_enabled" wire:change="saveRule('lapsed_customer')" />
                </div>

                @if($rules['lapsed_customer']['is_enabled'])
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 animate-in fade-in slide-in-from-top-4">
                        <flux:field>
                            <flux:label>Inactivity Window (Days)</flux:label>
                            <flux:input type="number" wire:model.blur="rules.lapsed_customer.config.lapsed_days" wire:change="saveRule('lapsed_customer')" />
                            <flux:description>Number of days since the last visit.</flux:description>
                        </flux:field>

                        <flux:field>
                            <flux:label>SMS Message Template</flux:label>
                            <flux:textarea wire:model.blur="rules.lapsed_customer.message_template" wire:change="saveRule('lapsed_customer')" rows="3" />
                            <flux:description>Use <code>{{name}}</code> as placeholder.</flux:description>
                        </flux:field>
                    </div>
                @endif
            </div>
        </div>
    </flux:card>

    <div class="flex items-center gap-3 p-4 bg-amber-50 border border-amber-200 rounded-lg">
        <flux:icon icon="information-circle" class="text-amber-600" />
        <flux:text size="sm" class="text-amber-800">
            <strong>Pro Tip:</strong> Automations are processed daily. Ensure you have sufficient SMS credits in your wallet to cover automated messages.
        </flux:text>
    </div>
</div>
