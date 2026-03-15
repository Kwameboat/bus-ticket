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

<?php
namespace App\Notifications;
use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TripReminderNotification extends Notification {
    use Queueable;
    public function __construct(public Booking $booking) {}
    public function via($n): array { return ['mail','database']; }
    public function toMail($n): MailMessage {
        return (new MailMessage)
            ->subject('⏰ Trip Reminder — Departing Tomorrow')
            ->greeting('Hello '.$n->name.',')
            ->line('This is a reminder that you have a trip tomorrow.')
            ->line('**Route:** '.($this->booking->trip->route->name ?? 'N/A'))
            ->line('**Departure:** '.($this->booking->trip->departs_at?->format('H:i') ?? 'N/A'))
            ->line('**Boarding Point:** '.($this->booking->boardingPoint->name ?? 'N/A'))
            ->line('**Seats:** '.$this->booking->seats->pluck('seat_label')->join(', '))
            ->action('View Ticket', route('passenger.bookings.show',$this->booking->booking_ref))
            ->line('Please arrive 30 minutes early. Have your ticket QR code ready.');
    }
    public function toArray($n): array {
        return ['type'=>'trip_reminder','booking_ref'=>$this->booking->booking_ref,'message'=>'Your trip departs tomorrow.'];
    }
}

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
