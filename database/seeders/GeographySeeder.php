<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{City, Terminal, Route, RouteBoardingPoint};
use Illuminate\Support\Str;

class GeographySeeder extends Seeder
{
    public function run(): void
    {
        // Ghana Cities
        $citiesData = [
            ['Accra',       'Greater Accra',  5.6037,  -0.1870,  true],
            ['Kumasi',      'Ashanti',         6.6885,  -1.6244,  true],
            ['Takoradi',    'Western',         4.8956,  -1.7554,  true],
            ['Cape Coast',  'Central',         5.1054,  -1.2466,  true],
            ['Tamale',      'Northern',        9.4034,  -0.8424,  true],
            ['Ho',          'Volta',           6.6100,   0.4700,  true],
            ['Sunyani',     'Bono',            7.3349,  -2.3304,  true],
            ['Koforidua',   'Eastern',         6.0943,  -0.2576,  false],
            ['Bolgatanga',  'Upper East',     10.7867,  -0.8514,  false],
            ['Wa',          'Upper West',     10.0600,  -2.5000,  false],
        ];

        $cities = [];
        foreach ($citiesData as [$name, $region, $lat, $lng, $popular]) {
            $cities[$name] = City::firstOrCreate(['slug' => Str::slug($name)], [
                'name' => $name, 'region' => $region, 'lat' => $lat, 'lng' => $lng,
                'is_popular' => $popular, 'status' => 'active',
            ]);
        }

        // Terminals
        $terminalsData = [
            ['Accra',      'Neoplan Station Accra',       'Graphic Road, Accra',                  5.5535, -0.2040, true],
            ['Accra',      'Accra Mall Terminal',          'Spintex Road, Accra',                  5.6139, -0.1618, false],
            ['Accra',      'Kaneshie Market Terminal',     'Kaneshie, Accra',                      5.5685, -0.2302, false],
            ['Accra',      'Circle (Kwame Nkrumah Interchange)', 'Circle, Accra',                5.5750, -0.2200, false],
            ['Kumasi',     'Asafo Market Terminal',        'Asafo, Kumasi',                        6.6777, -1.6245, true],
            ['Kumasi',     'Kejetia Terminal',             'Kejetia, Kumasi',                      6.6900, -1.6220, false],
            ['Kumasi',     'Suame Terminal',               'Suame, Kumasi',                        6.7250, -1.6000, false],
            ['Takoradi',   'Takoradi Market Terminal',     'Market Circle, Takoradi',              4.8956, -1.7554, true],
            ['Cape Coast', 'Cape Coast Central Terminal',  'Commercial Street, Cape Coast',        5.1054, -1.2466, true],
            ['Tamale',     'Tamale Motor Park',            'Town Centre, Tamale',                  9.4034, -0.8424, true],
            ['Ho',         'Ho Bus Terminal',              'Ho Town Centre, Volta Region',          6.6100,  0.4700, true],
            ['Sunyani',    'Sunyani Central Terminal',     'Central Sunyani, Bono Region',          7.3349, -2.3304, true],
        ];

        $terminals = [];
        foreach ($terminalsData as [$city, $name, $address, $lat, $lng, $main]) {
            $terminals[$name] = Terminal::firstOrCreate(['name' => $name], [
                'city_id' => $cities[$city]->id, 'address' => $address,
                'lat' => $lat, 'lng' => $lng,
                'is_main_terminal' => $main, 'status' => 'active',
            ]);
        }

        // Routes
        $routesData = [
            ['Accra', 'Kumasi',     'Accra — Kumasi Express',     270, 210, 'Neoplan Station Accra',       'Asafo Market Terminal'],
            ['Accra', 'Takoradi',   'Accra — Takoradi Express',   220, 180, 'Neoplan Station Accra',       'Takoradi Market Terminal'],
            ['Accra', 'Cape Coast', 'Accra — Cape Coast',         145, 120, 'Kaneshie Market Terminal',    'Cape Coast Central Terminal'],
            ['Kumasi','Tamale',     'Kumasi — Tamale',            390, 300, 'Asafo Market Terminal',       'Tamale Motor Park'],
            ['Accra', 'Ho',         'Accra — Ho',                 168, 150, 'Accra Mall Terminal',         'Ho Bus Terminal'],
            ['Accra', 'Sunyani',    'Accra — Sunyani',            330, 270, 'Neoplan Station Accra',       'Sunyani Central Terminal'],
        ];

        $routes = [];
        foreach ($routesData as [$from, $to, $name, $dist, $dur, $fromTermName, $toTermName]) {
            $slug = Str::slug($name).'-'.Str::random(4);
            $r = Route::firstOrCreate(['name' => $name], [
                'origin_city_id'            => $cities[$from]->id,
                'destination_city_id'       => $cities[$to]->id,
                'origin_terminal_id'        => $terminals[$fromTermName]->id,
                'destination_terminal_id'   => $terminals[$toTermName]->id,
                'slug'                      => $slug,
                'distance_km'               => $dist,
                'duration_minutes'          => $dur,
                'status'                    => 'active',
            ]);
            $routes[$name] = $r;

            // Boarding points
            RouteBoardingPoint::firstOrCreate(['route_id'=>$r->id,'terminal_id'=>$terminals[$fromTermName]->id], [
                'point_type'=>'both','stop_order'=>0,'is_origin'=>true,'is_destination'=>false,
            ]);
            RouteBoardingPoint::firstOrCreate(['route_id'=>$r->id,'terminal_id'=>$terminals[$toTermName]->id], [
                'point_type'=>'both','stop_order'=>99,'is_origin'=>false,'is_destination'=>true,
            ]);
        }

        // Extra boarding points on Accra-Kumasi (via Accra Circle)
        $accraKumasi = $routes['Accra — Kumasi Express'] ?? null;
        if ($accraKumasi && isset($terminals['Circle (Kwame Nkrumah Interchange)'])) {
            RouteBoardingPoint::firstOrCreate(
                ['route_id'=>$accraKumasi->id,'terminal_id'=>$terminals['Circle (Kwame Nkrumah Interchange)']->id],
                ['point_type'=>'boarding','stop_order'=>1]
            );
        }

        $this->command->info('Geography seeded: '.count($cities).' cities, '.count($terminals).' terminals, '.count($routes).' routes.');
    }
}
