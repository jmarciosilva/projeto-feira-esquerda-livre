<?php

use App\Enums\CampaignStatus;
use App\Jobs\SendEmailCampaignJob;
use App\Jobs\TrackShipmentsJob;
use App\Models\EmailCampaign;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Atualiza status de envios em trânsito 3× por dia (08h, 14h, 20h)
Schedule::job(new TrackShipmentsJob())->cron('0 8,14,20 * * *');

// Expurga eventos de Customer Intelligence fora da janela de retencao (180 dias).
// Os agregados diarios de ci_daily_metrics sao permanentes e nao sao tocados.
// De madrugada, quando o trafego e minimo; withoutOverlapping porque uma
// primeira execucao sobre historico grande pode passar da janela de uma hora.
Schedule::command('customer-intelligence:prune-events')
    ->dailyAt('03:20')
    ->withoutOverlapping()
    ->name('customer-intelligence-prune');

// Dispara campanhas de email marketing agendadas a cada 5 minutos
Schedule::call(function () {
    EmailCampaign::where('status', CampaignStatus::Scheduled)
        ->where('scheduled_at', '<=', now())
        ->each(fn ($c) => SendEmailCampaignJob::dispatch($c->id)->onQueue('email-marketing'));
})->everyFiveMinutes()->name('dispatch-scheduled-campaigns');
