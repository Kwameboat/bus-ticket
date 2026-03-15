<?php
namespace App\Notifications;
use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class BookingConfirmedNotification extends Notification {
    use Queueable;
    public function __construct(public Booking $booking) {}
    public function via($n): array { return ['mail','database']; }
    public function toMail($n): MailMessage {
        return (new MailMessage)
            ->subject('✅ Booking Confirmed — '.$this->booking->booking_ref)
            ->greeting('Hello '.$n->name.'!')
            ->line('Your booking has been confirmed.')
            ->line('**Booking Reference:** '.$this->booking->booking_ref)
            ->line('**Route:** '.$this->booking->trip->route->name ?? 'N/A')
            ->line('**Date:** '.($this->booking->trip->departs_at?->format('D, d M Y H:i') ?? 'N/A'))
            ->line('**Seats:** '.$this->booking->seats->pluck('seat_label')->join(', '))
            ->line('**Amount Paid:** GHS '.number_format($this->booking->grand_total,2))
            ->action('View Ticket', route('passenger.bookings.show',$this->booking->booking_ref))
            ->line('Please arrive at the terminal 30 minutes before departure.');
    }
    public function toArray($n): array {
        return ['booking_ref'=>$this->booking->booking_ref,'type'=>'booking_confirmed','message'=>'Your booking '.$this->booking->booking_ref.' is confirmed.'];
    }
}
