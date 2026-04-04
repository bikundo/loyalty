<?php

use Illuminate\Foundation\Inspiring;
use App\Jobs\ResetDailyCashierCapsJob;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new ResetDailyCashierCapsJob())->dailyAt('00:00');
