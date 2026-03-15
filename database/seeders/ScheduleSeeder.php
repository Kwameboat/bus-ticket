<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Operator, Bus, SeatPlan, Schedule, Fare, Trip, TripSeat, Route, Terminal, Conductor};
use Carbon\Carbon;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $op1  = Operator::where('slug','vanefstc')->first();
        $op2  = Operator::where('slug','oa-travel')->first();
        $op3  = Operator::where('slug','starlite')->first();

        $routes = Route::with(['originTerminal','destinationTerminal'])->get()->keyBy('name');
        $buses  = Bus::with(['seatPlan','operator'])->get();

        if ($buses->isEmpty()) { $this->command->warn('No buses found. Run FleetSeeder first.'); return; }

        $schedulesData = [
            // [route_name, bus_index, operator, depart, arrive, recur, days]
            ['Accra — Kumasi Express',   0, $op1, '06:00', '09:30', 'daily', null],
            ['Accra — Kumasi Express',   1, $op1, '10:00', '13:30', 'daily', null],
            ['Accra — Kumasi Express',   2, $op2, '14:00', '17:30', 'daily', null],
            ['Accra — Takoradi Express', 3, $op2, '07:00', '10:00', 'daily', null],
            ['Accra — Takoradi Express', 4, $op3, '13:00', '16:00', 'daily', null],
            ['Accra — Cape Coast',       0, $op1, '08:00', '10:00', 'daily', null],
            ['Accra — Cape Coast',       2, $op2, '15:00', '17:00', 'daily', null],
            ['Kumasi — Tamale',          1, $op1, '07:00', '12:00', 'daily', null],
            ['Accra — Ho',               3, $op2, '09:00', '11:30', 'daily', null],
            ['Accra — Sunyani',          4, $op3, '07:30', '12:00', 'daily', null],
        ];

        $conductor = Conductor::first();

        foreach ($schedulesData as [$routeName, $busIdx, $op, $dep, $arr, $recur, $days]) {
            $route = $routes[$routeName] ?? null;
            $bus   = $buses->values()->get($busIdx % $buses->count());
            if (!$route || !$bus) continue;

            $schedule = Schedule::firstOrCreate(
                ['route_id'=>$route->id,'bus_id'=>$bus->id,'departure_time'=>$dep],
                [
                    'operator_id'      => $op->id,
                    'conductor_id'     => $conductor?->id,
                    'departure_time'   => $dep,
                    'arrival_time'     => $arr,
                    'recurrence_type'  => $recur,
                    'recurrence_days'  => $days,
                    'valid_from'       => today()->subDays(30),
                    'valid_to'         => today()->addDays(90),
                    'waitlist_enabled' => true,
                    'status'           => 'active',
                ]
            );

            // Create fares (origin → destination)
            $boarding = $route->originTerminal;
            $dropoff  = $route->destinationTerminal;

            if ($boarding && $dropoff) {
                // Calculate fare based on distance
                $baseFare = match(true) {
                    $route->distance_km <= 150 => 40.00,
                    $route->distance_km <= 220 => 65.00,
                    $route->distance_km <= 270 => 80.00,
                    $route->distance_km <= 330 => 100.00,
                    default                    => 120.00,
                };

                // Premium for VIP/Executive
                if (in_array($bus->bus_type, ['VIP','Executive'])) $baseFare *= 1.3;

                Fare::firstOrCreate(
                    ['schedule_id'=>$schedule->id,'boarding_point_id'=>$boarding->id,'dropoff_point_id'=>$dropoff->id],
                    ['base_fare'=>round($baseFare,2),'tax_amount'=>0.00,'service_charge'=>round($baseFare * 0.02, 2),'currency'=>'GHS','is_active'=>true]
                );
            }

            // Generate trips for next 14 days + last 3 days
            $this->generateTrips($schedule, $bus, $route, $conductor);
        }

        $this->command->info('Schedules and trips seeded.');
    }

    private function generateTrips(Schedule $schedule, Bus $bus, Route $route, ?Conductor $conductor): void
    {
        $plan   = $bus->seatPlan;
        $seats  = $plan ? $plan->getSeatLabels() : [];
        $total  = count($seats) ?: $bus->capacity;

        for ($i = -3; $i <= 14; $i++) {
            $date  = today()->addDays($i);
            $depAt = Carbon::parse($date->format('Y-m-d').' '.$schedule->departure_time);
            $arrAt = Carbon::parse($date->format('Y-m-d').' '.$schedule->arrival_time);
            if ($arrAt->lte($depAt)) $arrAt->addDay();

            $status = 'scheduled';
            if ($i < 0)  $status = 'arrived';
            if ($i === 0 && now()->gte($depAt)) $status = 'in_transit';

            $trip = Trip::firstOrCreate(
                ['schedule_id'=>$schedule->id,'trip_date'=>$date->format('Y-m-d')],
                [
                    'bus_id'          => $bus->id,
                    'route_id'        => $route->id,
                    'operator_id'     => $schedule->operator_id,
                    'conductor_id'    => $conductor?->id,
                    'departs_at'      => $depAt,
                    'arrives_at'      => $arrAt,
                    'status'          => $status,
                    'total_seats'     => $total,
                    'available_seats' => $total,
                    'waitlist_enabled'=> true,
                ]
            );

            // Generate seat rows for future trips
            if ($trip->wasRecentlyCreated && !empty($seats)) {
                foreach ($seats as $idx => $label) {
                    TripSeat::firstOrCreate(
                        ['trip_id'=>$trip->id,'seat_number'=>(string)($idx+1)],
                        ['seat_label'=>$label,'status'=>'available']
                    );
                }
            }
        }
    }
}
