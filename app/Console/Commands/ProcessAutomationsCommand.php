<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AutomationService;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Attributes\Description;

#[Signature('automations:process')]
#[Description('Command description')]
class ProcessAutomationsCommand extends Command
{
    protected $description = 'Process time-based customer automations (Birthdays, Lapsed Customers)';

    public function handle(AutomationService $automationService): void
    {
        $this->info('Starting automated campaign processing...');

        $automationService->runTimeBasedAutomations();

        $this->info('Automated campaigns processed successfully.');
    }
}
