<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bus;
use App\Models\BusRoute;
use App\Models\City;
use App\Models\Fare;
use App\Models\Schedule;
use Illuminate\Http\Request;

class FleetController extends Controller
{
    public function buses()
    {
        $buses = Bus::with('operator')->latest()->paginate(20);
        return view('admin.fleet.buses', compact('buses'));
    }

    public function storeBus(Request $request)
    {
        $request->validate(['plate' => ['required', 'string'], 'capacity' => ['required', 'integer']]);
        Bus::create($request->only('plate', 'capacity', 'operator_id', 'type'));
        return back()->with('success', 'Bus added.');
    }

    public function routes()
    {
        $routes = BusRoute::with(['originCity', 'destinationCity'])->latest()->paginate(20);
        return view('admin.fleet.routes', compact('routes'));
    }

    public function storeRoute(Request $request)
    {
        $request->validate(['origin_city_id' => ['required'], 'destination_city_id' => ['required']]);
        BusRoute::create($request->only('origin_city_id', 'destination_city_id', 'distance_km', 'duration_minutes'));
        return back()->with('success', 'Route added.');
    }

    public function schedules()
    {
        $schedules = Schedule::with(['route', 'bus'])->latest()->paginate(20);
        return view('admin.fleet.schedules', compact('schedules'));
    }

    public function storeSchedule(Request $request)
    {
        $request->validate(['route_id' => ['required'], 'bus_id' => ['required'], 'departure_date' => ['required', 'date']]);
        Schedule::create($request->only('route_id', 'bus_id', 'operator_id', 'departure_date', 'departure_time', 'arrival_time', 'status'));
        return back()->with('success', 'Schedule added.');
    }

    public function storeFare(Request $request)
    {
        $request->validate(['schedule_id' => ['required'], 'base_fare' => ['required', 'numeric']]);
        Fare::create($request->only('schedule_id', 'boarding_point_id', 'dropoff_point_id', 'base_fare', 'tax_amount', 'service_charge'));
        return back()->with('success', 'Fare added.');
    }

    public function cities()
    {
        $cities = City::orderBy('name')->paginate(20);
        return view('admin.fleet.cities', compact('cities'));
    }

    public function storeCity(Request $request)
    {
        $request->validate(['name' => ['required', 'string', 'max:255']]);
        City::create($request->only('name', 'region', 'is_active'));
        return back()->with('success', 'City added.');
    }

    public function terminals()
    {
        return view('admin.fleet.terminals', ['terminals' => []]);
    }

    public function storeTerminal(Request $request)
    {
        return back()->with('success', 'Terminal added.');
    }
}
