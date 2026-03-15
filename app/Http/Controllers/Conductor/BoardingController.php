<?php
namespace App\Http\Controllers\Conductor;

use App\Http\Controllers\Controller;
use App\Models\{Trip, BoardingSession, QrTicket, TicketScan};
use App\Services\QrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BoardingController extends Controller
{
    public function __construct(private QrService $qrService) {}

    public function dashboard()
    {
        $user    = Auth::user();
        $conductor = $user->conductor;

        // Today's trips for this conductor
        $trips = Trip::whereDate('departs_at', today())
            ->where('conductor_id', $conductor?->id)
            ->with(['route.originCity','route.destinationCity','bus','operator'])
            ->orderBy('departs_at')->get();

        return view('conductor.dashboard', compact('trips','conductor'));
    }

    public function boardingPanel(Trip $trip)
    {
        $this->authorizeConductorForTrip($trip);
        $trip->load(['route.originCity','route.destinationCity','bus','operator','bookings.seats']);

        // Get or create boarding session
        $session = BoardingSession::firstOrCreate(
            ['trip_id'=>$trip->id,'conductor_id'=>Auth::id()],
            ['started_at'=>now(),'is_active'=>true]
        );

        $recentScans = TicketScan::where('trip_id',$trip->id)
            ->with('qrTicket','scannedBy')
            ->latest()->take(20)->get();

        $stats = [
            'total_seats'   => $trip->total_seats,
            'boarded'       => $trip->boarded_count,
            'remaining'     => $trip->total_seats - $trip->boarded_count,
            'valid_scans'   => TicketScan::where('trip_id',$trip->id)->where('scan_result','valid')->count(),
            'invalid_scans' => TicketScan::where('trip_id',$trip->id)->where('boarding_granted',false)->count(),
        ];

        return view('conductor.boarding-panel', compact('trip','session','recentScans','stats'));
    }

    /** QR scan endpoint — called via AJAX from mobile */
    public function scan(Request $request)
    {
        $request->validate([
            'qr_token' => 'required_without:ticket_number|string',
            'ticket_number' => 'required_without:qr_token|string',
            'trip_id'  => 'required|exists:trips,id',
            'method'   => 'in:qr_camera,qr_manual,manual_override',
        ]);

        $trip   = Trip::findOrFail($request->trip_id);
        $method = $request->get('method','qr_camera');

        $this->authorizeConductorForTrip($trip);

        // Validate the QR
        if ($request->qr_token) {
            $result = $this->qrService->validate($request->qr_token, $trip);
        } else {
            $result = $this->qrService->validateByTicketNumber($request->ticket_number, $trip);
            $method = 'qr_manual';
        }

        // If valid, mark as boarded
        if ($result['boarding_granted'] && $result['ticket']) {
            $scan = $this->qrService->markBoarded(
                $result['ticket'],
                Auth::id(),
                $trip,
                $method,
                $request->userAgent()
            );
        } else {
            // Log failed scan
            if ($result['ticket']) {
                TicketScan::create([
                    'qr_ticket_id'    => $result['ticket']->id,
                    'scanned_by'      => Auth::id(),
                    'trip_id'         => $trip->id,
                    'scan_method'     => $method,
                    'scan_result'     => $result['result'],
                    'ip_address'      => $request->ip(),
                    'boarding_granted'=> false,
                    'notes'           => $result['message'],
                ]);
                BoardingSession::where('trip_id',$trip->id)->where('is_active',true)
                    ->increment('total_scanned');
                BoardingSession::where('trip_id',$trip->id)->where('is_active',true)
                    ->increment('invalid_scans');
            }
        }

        return response()->json([
            'result'          => $result['result'],
            'message'         => $result['message'],
            'boarding_granted'=> $result['boarding_granted'],
            'passenger_name'  => $result['passenger_name'],
            'seat_label'      => $result['seat_label'],
            'boarded_count'   => $trip->fresh()->boarded_count,
        ]);
    }

    public function history(Trip $trip)
    {
        $this->authorizeConductorForTrip($trip);
        $scans = TicketScan::where('trip_id',$trip->id)
            ->with(['qrTicket.bookingSeat','scannedBy'])
            ->latest()->paginate(50);
        return view('conductor.scan-history', compact('trip','scans'));
    }

    private function authorizeConductorForTrip(Trip $trip): void
    {
        $conductor = Auth::user()->conductor;
        // Super admin can access any trip; conductors only their assigned trips
        if (!Auth::user()->isAdmin() && $trip->conductor_id !== $conductor?->id) {
            abort(403, 'You are not assigned to this trip.');
        }
    }
}
