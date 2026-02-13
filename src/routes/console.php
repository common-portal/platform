<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('directdebit:process-collections')
    ->dailyAt('01:00')
    ->timezone('UTC')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/directdebit-cron.log'));
