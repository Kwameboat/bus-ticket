<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Bus, Operator, SeatPlan, City, Terminal, Route, Schedule, Fare, Trip};
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

class FleetController extends Controller
{
    public function buses(Request $request)
    {
        $buses = Bus::with('operator')->when($request->search, fn($q)=>$q->where('name','like',"%{$request->search}%")->orWhere('reg_number','like',"%{$request->search}%"))->latest()->paginate(20);
        $operators = Operator::active()->get();
        return view('admin.buses.index', compact('buses','operators'));
    }

    public function storeBus(Request $request)
    {
        $data = $request->validate([
            'operator_id'=>'required|exists:operators,id','name'=>'required|string|max:200',
            'reg_number'=>'required|string|max:100|unique:buses','plate_number'=>'nullable|string|max:50',
            'bus_type'=>'required|string','capacity'=>'required|integer|min:1|max:100',
            'amenities'=>'nullable|array','make'=>'nullable|string','model'=>'nullable|string',
            'year'=>'nullable|integer|min:1990|max:'.date('Y'),'image'=>'nullable|image|max:2048',
        ]);
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('buses','public');
        }
        $data['amenities'] = $request->amenities ?? [];
        Bus::create($data);
        return back()->with('success','Bus added successfully.');
    }

    public function routes(Request $request)
    {
        $routes = Route::with(['originCity','destinationCity'])->when($request->search,fn($q)=>$q->where('name','like',"%{$request->search}%"))->latest()->paginate(20);
        $cities = City::active()->orderBy('name')->get();
        return view('admin.routes.index', compact('routes','cities'));
    }

    public function storeRoute(Request $request)
    {
        $data = $request->validate([
            'origin_city_id'=>'required|exists:cities,id','destination_city_id'=>'required|exists:cities,id|different:origin_city_id',
            'name'=>'required|string|max:300','distance_km'=>'nullable|integer','duration_minutes'=>'nullable|integer',
        ]);
        $data['slug'] = Str::slug($data['name'].'-'.Str::random(4));
        Route::create($data);
        return back()->with('success','Route created.');
    }

    public function schedules(Request $request)
    {
        $schedules = Schedule::with(['route.originCity','route.destinationCity','bus','operator'])->latest()->paginate(20);
        $routes    = Route::active()->with(['originCity','destinationCity'])->get();
        $buses     = Bus::where('status','active')->get();
        $operators = Operator::where('status','active')->get();
        return view('admin.schedules.index', compact('schedules','routes','buses','operators'));
    }

    public function storeSchedule(Request $request)
    {
        $data = $request->validate([
            'route_id'=>'required|exists:routes,id','bus_id'=>'required|exists:buses,id',
            'operator_id'=>'required|exists:operators,id','departure_time'=>'required',
            'arrival_time'=>'required','recurrence_type'=>'required|in:once,daily,weekly,custom',
            'valid_from'=>'required|date','valid_to'=>'nullable|date|after:valid_from',
            'recurrence_days'=>'nullable|array','driver_id'=>'nullable|exists:drivers,id',
            'conductor_id'=>'nullable|exists:conductors,id',
        ]);
        $schedule = Schedule::create($data);

        // Auto-generate trips for next 30 days if recurring
        if (in_array($data['recurrence_type'],['daily','weekly','custom'])) {
            $this->generateTrips($schedule, 30);
        } elseif ($data['recurrence_type'] === 'once') {
            $this->generateTrips($schedule, 1);
        }

        return back()->with('success','Schedule created and trips generated.');
    }

    private function generateTrips(Schedule $schedule, int $days): void
    {
        $bus    = $schedule->bus;
        $plan   = $bus->seatPlan;
        $seats  = $plan ? $plan->getSeatLabels() : [];
        $total  = count($seats) ?: $bus->capacity;

        $from = max(Carbon::parse($schedule->valid_from), today());
        for ($i = 0; $i < $days; $i++) {
            $date = $from->copy()->addDays($i);
            if ($schedule->valid_to && $date->gt(Carbon::parse($schedule->valid_to))) break;

            if ($schedule->recurrence_type === 'weekly' && $schedule->recurrence_days) {
                if (!in_array($date->dayOfWeek, $schedule->recurrence_days)) continue;
            }
            if ($schedule->recurrence_type === 'once' && $i > 0) break;

            $depAt = Carbon::parse($date->format('Y-m-d').' '.$schedule->departure_time);
            $arrAt = Carbon::parse($date->format('Y-m-d').' '.$schedule->arrival_time);
            if ($arrAt->lte($depAt)) $arrAt->addDay();

            $trip = Trip::firstOrCreate(
                ['schedule_id'=>$schedule->id,'trip_date'=>$date->format('Y-m-d')],
                [
                    'bus_id'=>$schedule->bus_id,'route_id'=>$schedule->route_id,
                    'operator_id'=>$schedule->operator_id,'driver_id'=>$schedule->driver_id,
                    'conductor_id'=>$schedule->conductor_id,'departs_at'=>$depAt,
                    'arrives_at'=>$arrAt,'status'=>'scheduled',
                    'total_seats'=>$total,'available_seats'=>$total,
                ]
            );

            // Generate seat rows
            if ($trip->wasRecentlyCreated && !empty($seats)) {
                foreach ($seats as $idx => $label) {
                    \App\Models\TripSeat::firstOrCreate(
                        ['trip_id'=>$trip->id,'seat_number'=>(string)($idx+1)],
                        ['seat_label'=>$label,'status'=>'available']
                    );
                }
            }
        }
    }

    public function storeFare(Request $request)
    {
        $data = $request->validate([
            'schedule_id'=>'required|exists:schedules,id','boarding_point_id'=>'required|exists:terminals,id',
            'dropoff_point_id'=>'required|exists:terminals,id|different:boarding_point_id',
            'base_fare'=>'required|numeric|min:0','tax_amount'=>'nullable|numeric|min:0',
            'service_charge'=>'nullable|numeric|min:0',
        ]);
        Fare::updateOrCreate(
            ['schedule_id'=>$data['schedule_id'],'boarding_point_id'=>$data['boarding_point_id'],'dropoff_point_id'=>$data['dropoff_point_id']],
            $data
        );
        return back()->with('success','Fare saved.');
    }

    public function cities()
    {
        $cities = City::orderBy('name')->paginate(30);
        return view('admin.cities.index', compact('cities'));
    }

    public function storeCity(Request $request)
    {
        $data = $request->validate(['name'=>'required|string|max:100','region'=>'nullable|string|max:100','is_popular'=>'boolean']);
        $data['slug'] = Str::slug($data['name']);
        City::create($data);
        return back()->with('success','City added.');
    }

    public function terminals()
    {
        $terminals = Terminal::with('city')->orderBy('name')->paginate(20);
        $cities    = City::active()->orderBy('name')->get();
        return view('admin.terminals.index', compact('terminals','cities'));
    }

    public function storeTerminal(Request $request)
    {
        $data = $request->validate([
            'city_id'=>'required|exists:cities,id','name'=>'required|string|max:200',
            'address'=>'nullable|string|max:500','is_main_terminal'=>'boolean',
        ]);
        Terminal::create($data);
        return back()->with('success','Terminal added.');
    }
}
