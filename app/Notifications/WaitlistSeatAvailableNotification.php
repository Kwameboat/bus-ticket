<?php
namespace App\Notifications;
use App\Models\{WaitlistEntry,Trip};
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class WaitlistSeatAvailableNotification extends Notification {
    use Queueable;
    public function __construct(public WaitlistEntry $entry, public Trip $trip) {}
    public function via($n): array { return ['mail','database']; }
    public function toMail($n): MailMessage {
        return (new MailMessage)
            ->subject('🎉 Seat Available — '.$this->trip->route->name ?? 'Your Route')
            ->greeting('Good news, '.$n->name.'!')
            ->line('A seat has become available on your waitlisted trip.')
            ->line('**Route:** '.($this->trip->route->name ?? 'N/A'))
            ->line('**Departure:** '.($this->trip->departs_at?->format('D, d M Y H:i') ?? 'N/A'))
            ->line('**Claim by:** '.($this->entry->claim_expires_at?->format('D, d M Y H:i') ?? 'N/A'))
            ->action('Book Now', route('trips.seats',$this->trip->id))
            ->line('Hurry! This offer expires at '.$this->entry->claim_expires_at?->format('H:i').'.');
    }
    public function toArray($n): array {
        return ['type'=>'waitlist_available','trip_id'=>$this->trip->id,'message'=>'A seat is available on your waitlisted trip.'];
    }
}
