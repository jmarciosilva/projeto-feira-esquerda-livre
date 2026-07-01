<?php

use App\Jobs\TrackShipmentsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Atualiza status de envios em trânsito 3× por dia (08h, 14h, 20h)
Schedule::job(new TrackShipmentsJob())->cron('0 8,14,20 * * *');
