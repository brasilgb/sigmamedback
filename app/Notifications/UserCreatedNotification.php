<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserCreatedNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Bem-vindo ao Meu Controle')
            ->view('emails.user-created', [
                'name' => $notifiable->name,
                'logoUrl' => asset('images/logo_branco.png'),
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'name' => $notifiable->name,
        ];
    }
}
