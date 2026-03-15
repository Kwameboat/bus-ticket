<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Operator, Bus, SeatPlan, Driver, Conductor, Schedule, Fare, Trip, TripSeat, Route, Terminal};
use Carbon\Carbon;

class FleetSeeder extends Seeder
{
    public function run(): void
    {
        $op1 = Operator::where('slug','vanefstc')->first();
        $op2 = Operator::where('slug','oa-travel')->first();
        $op3 = Operator::where('slug','starlite')->first();

        if (!$op1 || !$op2) { $this->command->warn('Operators not found. Run UsersSeeder first.'); return; }

        // Drivers
        $drivers = [
            Driver::firstOrCreate(['license_number'=>'GH-DL-001'], ['operator_id'=>$op1->id,'name'=>'Kweku Addo','phone'=>'0244200001','status'=>'active']),
            Driver::firstOrCreate(['license_number'=>'GH-DL-002'], ['operator_id'=>$op1->id,'name'=>'Isaac Nkrumah','phone'=>'0244200002','status'=>'active']),
            Driver::firstOrCreate(['license_number'=>'GH-DL-003'], ['operator_id'=>$op2->id,'name'=>'Samuel Adjei','phone'=>'0244200003','status'=>'active']),
        ];

        // Buses with seat plans
        $busesData = [
            [$op1->id, 'VanefSTC Comfort 1', 'GR-1234-23', 'Executive', 45, ['AC','WiFi','Charging','Reclining Seats']],
            [$op1->id, 'VanefSTC Comfort 2', 'GR-5678-23', 'Standard', 52, ['AC','Charging']],
            [$op2->id, 'OA Gold Express', 'GR-9101-22', 'VIP', 30, ['AC','WiFi','Charging','TV','Toilet']],
            [$op2->id, 'OA Standard Express', 'GR-1121-21', 'Standard', 45, ['AC']],
            [$op3->id, 'Starlite Morning Star', 'GR-3141-23', 'Executive', 40, ['AC','WiFi','Charging']],
        ];

        $buses = [];
        foreach ($busesData as [$opId, $name, $reg, $type, $cap, $amenities]) {
            $bus = Bus::firstOrCreate(['reg_number'=>$reg], [
                'operator_id'=>$opId,'name'=>$name,'plate_number'=>$reg,
                'bus_type'=>$type,'capacity'=>$cap,'amenities'=>$amenities,'status'=>'active',
            ]);
            $layout = $this->generateLayout($cap);
            SeatPlan::firstOrCreate(['bus_id'=>$bus->id,'name'=>$name.' Plan'], [
                'rows'=>count($layout),'cols'=>4,'layout'=>$layout,'is_default'=>true,
            ]);
            $buses[] = $bus;
        }

        $this->command->info('Fleet seeded: '.count($buses).' buses.');
    }

    private function generateLayout(int $capacity): array
    {
        $layout = [];
        $seatNum = 1;
        $rows = ceil($capacity / 4);
        for ($r = 0; $r < $rows; $r++) {
            $row = [];
            for ($c = 0; $c < 4; $c++) {
                if ($c === 2) $row[] = null; // aisle
                if ($seatNum <= $capacity) {
                    $row[] = chr(65+$r).($c < 2 ? $c+1 : $c);
                    $seatNum++;
                } else {
                    $row[] = null;
                }
            }
            $layout[] = $row;
        }
        return $layout;
    }
}
