<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    public function __construct(
        public string $token,
        public string $workspace,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
            'workspace' => $this->workspace,
        ], false));

        $minutes = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        return (new MailMessage)
            ->subject('Reset your '.config('app.name').' password')
            ->greeting('Hello!')
            ->line('We received a request to reset the password for your workspace account.')
            ->line('Workspace: **'.$this->workspace.'**')
            ->action('Reset password', $url)
            ->line('This link expires in '.$minutes.' minutes.')
            ->line('If you did not request a reset, you can ignore this email.');
    }
}
