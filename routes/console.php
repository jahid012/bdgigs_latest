<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('orders:send-requirement-reminders')->hourly();
Schedule::command('orders:send-deadline-reminders')->hourly();
Schedule::command('orders:mark-overdue')->hourly();
Schedule::command('custom-offers:expire')->everyFifteenMinutes();
Schedule::command('messages:send-unread-reminders')->everyFifteenMinutes();
Schedule::command('reviews:send-deadline-reminders')->dailyAt('09:00');
Schedule::command('reviews:expire-periods')->dailyAt('00:30');
Schedule::command('users:send-profile-completion-reminders')->dailyAt('10:00');
Schedule::command('gigs:send-performance-summary')->weeklyOn(1, '10:30');
Schedule::command('marketing:send-weekly-digest')->weeklyOn(1, '11:00');
Schedule::command('marketing:send-recently-viewed-reminders')->dailyAt('12:00');
Schedule::command('marketing:send-saved-gig-reminders')->weeklyOn(3, '12:30');
Schedule::command('marketing:send-checkout-abandonment')->hourly();
Schedule::command('marketing:send-reengagement-emails')->dailyAt('14:00');
