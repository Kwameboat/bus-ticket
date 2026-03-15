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
