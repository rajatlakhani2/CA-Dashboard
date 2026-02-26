<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

Schedule::command('services:generate-dues')->daily();
Schedule::command('services:notify-dues')->dailyAt('09:00');
Schedule::command('reminders:personal-renewals')->dailyAt('08:00');
