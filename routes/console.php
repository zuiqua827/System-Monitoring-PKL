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
| Synchronize real students from the SiPintu Gateway daily.
| The command is safe to run and only touches student data.
|
*/
Schedule::command('sipintu:sync-students')->dailyAt('02:00');
