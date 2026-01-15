<?php

namespace App\Providers;

use App\Support\TenantContext;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        ResetPassword::createUrlUsing(function (mixed $notifiable, string $token) {
            $frontendUrl = rtrim((string) env('FRONTEND_URL', 'http://localhost:3000'), '/');
            $tenantSlug = TenantContext::tenant()?->slug;
            $email = method_exists($notifiable, 'getEmailForPasswordReset')
                ? (string) $notifiable->getEmailForPasswordReset()
                : (string) ($notifiable->email ?? '');

            $qs = http_build_query([
                'token' => $token,
                'email' => $email,
                'tenant' => $tenantSlug,
            ]);

            return $frontendUrl.'/reset-password?'.$qs;
        });

        Event::listen(MessageSent::class, function (MessageSent $event) {
            $to = [];

            foreach ($event->message->getTo() ?? [] as $address) {
                $to[] = $address->getAddress();
            }

            Log::info('mail.sent', [
                'mailer' => config('mail.default'),
                'to' => $to,
                'subject' => $event->message->getSubject(),
            ]);
        });

        Event::listen(NotificationSent::class, function (NotificationSent $event) {
            Log::info('notification.sent', [
                'channel' => $event->channel,
                'notification' => get_class($event->notification),
                'notifiable_type' => is_object($event->notifiable) ? get_class($event->notifiable) : null,
                'notifiable_id' => is_object($event->notifiable) && isset($event->notifiable->id) ? $event->notifiable->id : null,
                'response' => $event->response,
            ]);
        });

        Event::listen(NotificationFailed::class, function (NotificationFailed $event) {
            Log::error('notification.failed', [
                'channel' => $event->channel,
                'notification' => get_class($event->notification),
                'notifiable_type' => is_object($event->notifiable) ? get_class($event->notifiable) : null,
                'notifiable_id' => is_object($event->notifiable) && isset($event->notifiable->id) ? $event->notifiable->id : null,
                'data' => $event->data,
            ]);
        });
    }
}
