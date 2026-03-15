<?php
namespace App\Notifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class WelcomeNotification extends Notification {
    use Queueable;
    public function via($n): array { return ['mail']; }
    public function toMail($n): MailMessage {
        return (new MailMessage)
            ->subject('Welcome to GhanaBus Connect!')
            ->greeting('Welcome, '.$n->name.'! 🚌')
            ->line('Thank you for joining GhanaBus Connect — Ghana\'s easiest bus booking platform.')
            ->line('You can now search and book tickets for intercity travel across Ghana.')
            ->action('Search Buses', url('/search'))
            ->line('Your account includes a free GhanaBus wallet for seamless payments.');
    }
}
