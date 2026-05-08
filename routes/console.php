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

try {
    $time1 = \App\Models\Setting::get('reminder_time_1', '10:00');
    $time2 = \App\Models\Setting::get('reminder_time_2', '18:00');
} catch (\Throwable $e) {
    $time1 = '10:00';
    $time2 = '18:00';
}

Schedule::command('tasks:send-reminders')->dailyAt($time1);
Schedule::command('tasks:send-reminders')->dailyAt($time2);
