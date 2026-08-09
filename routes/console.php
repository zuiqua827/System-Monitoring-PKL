<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
|
| Synchronize real students and teachers from the SiPintu Gateway daily.
| The command is safe to run and only touches student/teacher data.
|
*/
Schedule::command('sipintu:sync')->dailyAt('02:00');
