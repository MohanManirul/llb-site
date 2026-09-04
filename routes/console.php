<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Upcoming-payment reminders. Runs once a day; each notification carries a
// `dedupe_key` under a unique index — keyed on (project, due date), and on
// today's date as well for overdue, so an overdue project is chased once every
// day until it is paid — while a re-run, a manual invocation or a restart still
// cannot notify the same client twice on the same day.
Schedule::command('payment:create-reminders')
    ->dailyAt('23:59')
    ->timezone('Asia/Dhaka')
    ->withoutOverlapping();
