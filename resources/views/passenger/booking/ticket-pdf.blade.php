<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:Arial,Helvetica,sans-serif; font-size:12px; color:#1e293b; background:#fff; }
        .ticket { max-width:680px; margin:20px auto; border:2px solid #1A56DB; border-radius:12px; overflow:hidden; }
        .header { background:#1A56DB; color:#fff; padding:20px 24px; }
        .header h1 { font-size:20px; font-weight:800; }
        .header p  { font-size:11px; color:#bfdbfe; margin-top:2px; }
        .badge { display:inline-block; background:#22c55e; color:#fff; font-size:10px; font-weight:700; padding:3px 10px; border-radius:20px; margin-top:6px; text-transform:uppercase; }
        .route { padding:20px 24px; display:flex; align-items:center; gap:16px; border-bottom:1px solid #e2e8f0; }
        .time { font-size:28px; font-weight:900; }
        .city { font-size:11px; color:#64748b; margin-top:2px; }
        .divider { flex:1; border-top:1px dashed #cbd5e1; }
        .dur   { font-size:10px; color:#94a3b8; text-align:center; white-space:nowrap; padding:0 8px; }
        .info  { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; padding:16px 24px; border-bottom:1px solid #e2e8f0; }
        .info-item label { font-size:9px; color:#94a3b8; text-transform:uppercase; letter-spacing:.5px; display:block; margin-bottom:3px; }
        .info-item span  { font-size:12px; font-weight:700; }
        .seats { padding:16px 24px; border-bottom:1px solid #e2e8f0; }
        .seats h3 { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; margin-bottom:10px; }
        .seat-row { display:flex; align-items:center; justify-content:space-between; margin-bottom:6px; }
        .seat-badge { background:#1A56DB; color:#fff; font-size:11px; font-weight:700; padding:3px 10px; border-radius:6px; }
        .qr-section { padding:16px 24px; display:flex; gap:16px; align-items:flex-start; }
        .qr-wrap { text-align:center; }
        .qr-wrap img { width:90px; height:90px; }
        .qr-wrap p { font-size:9px; color:#94a3b8; margin-top:4px; }
        .ticket-no { font-family:monospace; font-size:10px; color:#64748b; background:#f1f5f9; padding:4px 8px; border-radius:6px; }
        .footer { background:#f8fafc; padding:12px 24px; display:flex; justify-content:space-between; align-items:center; }
        .footer p { font-size:10px; color:#94a3b8; }
        .amount { font-size:18px; font-weight:900; color:#1A56DB; }
    </style>
</head>
<body>
<div class="ticket">
    <div class="header">
        <h1>🚌 GhanaBus Connect</h1>
        <p>E-Ticket / Boarding Pass</p>
        <span class="badge">{{ strtoupper($booking->booking_status) }}</span>
    </div>

    <div class="route">
        <div>
            <div class="time">{{ $booking->trip->departs_at->format('H:i') }}</div>
            <div class="city">{{ $booking->boardingPoint->name }}</div>
            <div class="city">{{ $booking->boardingPoint->city->name }}</div>
        </div>
        <div class="divider"></div>
        <div class="dur">{{ $booking->trip->route->duration_formatted }}</div>
        <div class="divider"></div>
        <div style="text-align:right">
            <div class="time">{{ $booking->trip->arrives_at->format('H:i') }}</div>
            <div class="city">{{ $booking->dropoffPoint->name }}</div>
            <div class="city">{{ $booking->dropoffPoint->city->name }}</div>
        </div>
    </div>

    <div class="info">
        <div class="info-item">
            <label>Booking Ref</label>
            <span>{{ $booking->booking_ref }}</span>
        </div>
        <div class="info-item">
            <label>Date</label>
            <span>{{ $booking->trip->departs_at->format('d M Y') }}</span>
        </div>
        <div class="info-item">
            <label>Operator</label>
            <span>{{ $booking->trip->operator->name }}</span>
        </div>
        <div class="info-item">
            <label>Bus</label>
            <span>{{ $booking->trip->bus->name }}</span>
        </div>
    </div>

    <div class="seats">
        <h3>Passenger(s)</h3>
        @foreach($booking->seats as $seat)
        <div class="seat-row">
            <div>
                <span style="font-weight:700">{{ $seat->passenger_name }}</span>
                @if($seat->passenger_phone)<span style="color:#64748b;font-size:11px;margin-left:8px">{{ $seat->passenger_phone }}</span>@endif
            </div>
            <span class="seat-badge">Seat {{ $seat->seat_label }}</span>
        </div>
        @endforeach
    </div>

    @if($booking->qrTickets->isNotEmpty())
    <div class="qr-section">
        <div style="flex:1">
            <p style="font-size:11px;color:#64748b;margin-bottom:8px">Show your QR code to the conductor when boarding. Each passenger has a unique code.</p>
            @if($booking->invoice_number)
            <p style="font-size:10px;color:#94a3b8;">Invoice: {{ $booking->invoice_number }}</p>
            @endif
        </div>
        @foreach($booking->qrTickets as $qr)
        <div class="qr-wrap">
            @if($qr->qr_image_path)
                <img src="{{ public_path('storage/'.$qr->qr_image_path) }}" alt="QR">
            @else
                <div style="width:90px;height:90px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;font-size:9px;color:#94a3b8;border:1px solid #e2e8f0;">QR Code</div>
            @endif
            <p class="ticket-no">{{ $qr->ticket_number }}</p>
            <p>Seat {{ $qr->seat_label }}</p>
        </div>
        @endforeach
    </div>
    @endif

    <div class="footer">
        <div>
            <p>Please arrive 30 minutes before departure.</p>
            <p>Booking issued: {{ $booking->created_at->format('d M Y H:i') }}</p>
        </div>
        <div class="amount">₵{{ number_format($booking->grand_total, 2) }}</div>
    </div>
</div>
</body>
</html>
