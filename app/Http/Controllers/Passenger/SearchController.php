<?php
namespace App\Http\Controllers\Passenger;

use App\Http\Controllers\Controller;
use App\Models\{City, Route, Trip, TripSeat, Fare, Terminal};
use Illuminate\Http\Request;
use Carbon\Carbon;

class SearchController extends Controller
{
    public function index()
    {
        $popularCities = City::active()->popular()->orderBy('name')->get();
        $cities        = City::active()->orderBy('name')->get();
        return view('passenger.search', compact('popularCities','cities'));
    }

    public function results(Request $request)
    {
        $request->validate([
            'origin'      => 'required|exists:cities,id',
            'destination' => 'required|exists:cities,id|different:origin',
            'date'        => 'required|date|after_or_equal:today',
        ]);

        $date        = Carbon::parse($request->date);
        $originCity  = City::findOrFail($request->origin);
        $destCity    = City::findOrFail($request->destination);

        $trips = Trip::with(['schedule','bus','operator','route.originCity','route.destinationCity','seats'])
            ->whereHas('route', fn($q)=>$q
                ->where('origin_city_id', $request->origin)
                ->where('destination_city_id', $request->destination)
                ->where('status','active')
            )
            ->whereDate('departs_at', $date)
            ->whereNotIn('status',['cancelled'])
            ->orderBy('departs_at')
            ->get()
            ->map(function($trip) {
                $trip->available_count = $trip->seats()->available()->count();
                return $trip;
            });

        return view('passenger.search-results', compact('trips','originCity','destCity','date'));
    }
}
