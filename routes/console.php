<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule automatic price scraping
Schedule::command('scrape:woolworths')->dailyAt('02:00');
Schedule::command('scrape:checkers')->dailyAt('03:00');
