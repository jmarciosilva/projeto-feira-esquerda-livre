<?php

namespace App\Providers;

use App\Events\OrderSplitConfirmed;
use App\Listeners\HandleAvaEnrollmentOnSplitConfirmed;
use App\Listeners\TrackOrderSplitConfirmedEvent;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::before(fn ($user) => $user->isAdmin() ? true : null);

        Event::listen(OrderSplitConfirmed::class, HandleAvaEnrollmentOnSplitConfirmed::class);
        Event::listen(OrderSplitConfirmed::class, TrackOrderSplitConfirmedEvent::class);

        $this->applyMailConfigFromDatabase();
    }

    private function applyMailConfigFromDatabase(): void
    {
        try {
            $settings = SiteSetting::instance();

            if (! $settings->mail_host) {
                return;
            }

            config([
                'mail.default' => $settings->mail_mailer ?? config('mail.default'),
                'mail.mailers.smtp.host' => $settings->mail_host,
                'mail.mailers.smtp.port' => $settings->mail_port ?? 587,
                'mail.mailers.smtp.username' => $settings->mail_username,
                'mail.mailers.smtp.password' => $settings->mail_password,
                'mail.mailers.smtp.encryption' => $settings->mail_encryption ?: null,
                'mail.from.address' => $settings->mail_from_address ?? config('mail.from.address'),
                'mail.from.name' => $settings->mail_from_name ?? config('mail.from.name'),
            ]);
        } catch (\Throwable) {
            // DB indisponível — mantém configuração do .env
        }
    }
}
