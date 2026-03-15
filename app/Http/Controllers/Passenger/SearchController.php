<?php

namespace App\Http\Controllers\Passenger;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Schedule;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index()
    {
        $cities = City::active()->orderBy('name')->get();
        return view('passenger.search', compact('cities'));
    }

    public function results(Request $request)
    {
        $request->validate([
            'from'  => ['required'],
            'to'    => ['required'],
            'date'  => ['required', 'date'],
        ]);

        $schedules = Schedule::with(['route', 'bus', 'operator'])
            ->whereHas('route', function ($q) use ($request) {
                $q->where('origin_city_id', $request->from)
                  ->where('destination_city_id', $request->to);
            })
            ->where('departure_date', $request->date)
            ->where('status', 'active')
            ->get();

        $cities = City::active()->orderBy('name')->get();

        return view('passenger.search-results', compact('schedules', 'cities'));
    }
}
