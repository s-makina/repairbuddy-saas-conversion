<?php

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;

class VerifyEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The tenant name for branding.
     *
     * @var string|null
     */
    protected $tenantName;

    /**
     * The tenant logo URL.
     *
     * @var string|null
     */
    protected $tenantLogoUrl;

    /**
     * The tenant slug (workspace ID).
     *
     * @var string|null
     */
    protected $tenantSlug;

    /**
     * Create a notification instance.
     */
    public function __construct(?string $tenantName = null, ?string $tenantLogoUrl = null, ?string $tenantSlug = null)
    {
        $this->tenantName = $tenantName;
        $this->tenantLogoUrl = $tenantLogoUrl;
        $this->tenantSlug = $tenantSlug;
    }

    /**
     * Get the notification's channels.
     *
     * @param  mixed  $notifiable
     * @return array|string
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        $workspaceUrl = null;
        if ($this->tenantSlug) {
            $domain = config('app.domain', '');
            if ($domain) {
                $workspaceUrl = 'https://' . $this->tenantSlug . '.' . $domain;
            }
        }

        return (new MailMessage)
            ->subject(Lang::get('Verify Email Address'))
            ->view('emails.auth.verify-email', [
                'verificationUrl' => $verificationUrl,
                'userName' => $notifiable->first_name ?? $notifiable->name ?? null,
                'userEmail' => $notifiable->email,
                'tenantName' => $this->tenantName ?? 'RepairBuddy',
                'tenantLogoUrl' => $this->tenantLogoUrl,
                'tenantSlug' => $this->tenantSlug,
                'workspaceUrl' => $workspaceUrl,
            ]);
    }

    /**
     * Get the verification URL for the given notifiable.
     *
     * @param  mixed  $notifiable
     * @return string
     */
    protected function verificationUrl($notifiable)
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
}
