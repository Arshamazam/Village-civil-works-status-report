<?php

use Illuminate\Support\Facades\Schedule;

// 12:00 PKT - Reminder emails for pending sites (MAIL-3)
Schedule::command('returns:remind')
    ->dailyAt('12:00')
    ->timezone('Asia/Karachi')
    ->withoutOverlapping();

// 13:00 PKT - Mark unsubmitted sites as Not Reported (ROLL-1, FORM-12)
Schedule::command('returns:mark-not-reported')
    ->dailyAt('13:00')
    ->timezone('Asia/Karachi')
    ->withoutOverlapping();

// 13:30 PKT - Rollup engine: site day snapshots & daily summary payload (ROLL-1, ROLL-2)
Schedule::command('progress:rollup')
    ->dailyAt('13:30')
    ->timezone('Asia/Karachi')
    ->withoutOverlapping();

// 14:00 PKT - Daily mobilization PDF report generation & email dispatch (RPT-1, MAIL-1)
Schedule::command('report:daily')
    ->dailyAt('14:00')
    ->timezone('Asia/Karachi')
    ->withoutOverlapping();

// Every minute - Shared hosting cron worker for database queue (MAIL-5, 2.4, 8.4)
Schedule::command('queue:work --stop-when-empty --max-time=50')
    ->everyMinute()
    ->withoutOverlapping();
