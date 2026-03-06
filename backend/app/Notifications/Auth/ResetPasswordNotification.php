<?php

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    /**
     * The password reset token.
     *
     * @var string
     */
    public $token;

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
     * Create a notification instance.
     */
    public function __construct(string $token, ?string $tenantName = null, ?string $tenantLogoUrl = null)
    {
        $this->token = $token;
        $this->tenantName = $tenantName;
        $this->tenantLogoUrl = $tenantLogoUrl;
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
        $resetUrl = $this->resetUrl($notifiable);

        $expire = Config::get('auth.passwords.'.Config::get('auth.defaults.passwords').'.expire', 60);

        return (new MailMessage)
            ->subject(Lang::get('Reset Password Notification'))
            ->view('emails.auth.reset-password', [
                'resetUrl' => $resetUrl,
                'expireMinutes' => $expire,
                'userName' => $notifiable->first_name ?? $notifiable->name ?? null,
                'userEmail' => $notifiable->email,
                'tenantName' => $this->tenantName ?? 'RepairBuddy',
                'tenantLogoUrl' => $this->tenantLogoUrl,
            ]);
    }

    /**
     * Get the reset URL for the given notifiable.
     *
     * @param  mixed  $notifiable
     * @return string
     */
    protected function resetUrl($notifiable)
    {
        $frontendUrl = rtrim((string) env('FRONTEND_URL', config('app.url')), '/');

        return $frontendUrl.'/reset-password?token='.$this->token.'&email='.urlencode($notifiable->getEmailForPasswordReset());
    }
}
