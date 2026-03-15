<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\{User, Wallet, Operator, Conductor};

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin
        $admin = User::firstOrCreate(['email' => 'admin@ghanabusconnect.com'], [
            'name' => 'System Administrator', 'phone' => '0200000001',
            'password' => Hash::make('Admin@123!'), 'email_verified_at' => now(), 'status' => 'active',
        ]);
        $admin->assignRole('super_admin');
        Wallet::firstOrCreate(['user_id' => $admin->id], ['balance' => 0]);

        // Operator Users
        $op1user = User::firstOrCreate(['email' => 'vanef@ghanabusconnect.com'], [
            'name' => 'VanefSTC Manager', 'phone' => '0200000002',
            'password' => Hash::make('Operator@123'), 'email_verified_at' => now(),
        ]);
        $op1user->assignRole('operator');
        Wallet::firstOrCreate(['user_id' => $op1user->id]);

        $op2user = User::firstOrCreate(['email' => 'oatravel@ghanabusconnect.com'], [
            'name' => 'OA Travel Manager', 'phone' => '0200000003',
            'password' => Hash::make('Operator@123'), 'email_verified_at' => now(),
        ]);
        $op2user->assignRole('operator');
        Wallet::firstOrCreate(['user_id' => $op2user->id]);

        // Operator records
        $op1 = Operator::firstOrCreate(['user_id' => $op1user->id], [
            'name' => 'VanefSTC', 'slug' => 'vanefstc', 'commission_rate' => 8.00,
            'email' => 'info@vanefstc.com', 'phone' => '0302000001',
            'description' => 'Ghana\'s premier long-distance bus service.', 'status' => 'active',
        ]);
        $op2 = Operator::firstOrCreate(['user_id' => $op2user->id], [
            'name' => 'OA Travel & Tours', 'slug' => 'oa-travel', 'commission_rate' => 10.00,
            'email' => 'info@oatravel.com.gh', 'phone' => '0302000002',
            'description' => 'Comfortable intercity travel across Ghana.', 'status' => 'active',
        ]);
        $op3 = Operator::firstOrCreate(['slug' => 'starlite'], [
            'user_id' => $admin->id, 'name' => 'Starlite Express', 'commission_rate' => 9.00,
            'phone' => '0302000003', 'status' => 'active',
        ]);

        // Conductor
        $condUser = User::firstOrCreate(['email' => 'conductor@ghanabusconnect.com'], [
            'name' => 'Emmanuel Asante', 'phone' => '0244100001',
            'password' => Hash::make('Conduct@123'), 'email_verified_at' => now(),
        ]);
        $condUser->assignRole('conductor');
        Conductor::firstOrCreate(['user_id' => $condUser->id], [
            'operator_id' => $op1->id, 'name' => 'Emmanuel Asante',
            'staff_id' => 'COND-001', 'phone' => '0244100001', 'status' => 'active',
        ]);
        Wallet::firstOrCreate(['user_id' => $condUser->id]);

        // Sample Passengers
        $passengers = [
            ['Kofi Mensah', 'kofi@example.com', '0244100002'],
            ['Ama Asante', 'ama@example.com', '0244100003'],
            ['Kwame Boateng', 'kwame@example.com', '0244100004'],
            ['Akosua Owusu', 'akosua@example.com', '0244100005'],
            ['Yaw Darko', 'yaw@example.com', '0244100006'],
            ['Abena Frimpong', 'abena@example.com', '0244100007'],
            ['Kojo Amponsah', 'kojo@example.com', '0244100008'],
            ['Efua Agyeman', 'efua@example.com', '0244100009'],
        ];

        foreach ($passengers as [$name, $email, $phone]) {
            $p = User::firstOrCreate(['email' => $email], [
                'name' => $name, 'phone' => $phone,
                'password' => Hash::make('Pass@1234'), 'email_verified_at' => now(),
            ]);
            $p->assignRole('passenger');
            $wallet = Wallet::firstOrCreate(['user_id' => $p->id]);
            // Give demo wallet balance
            if ($wallet->balance == 0) {
                $wallet->update(['balance' => rand(50, 500)]);
            }
        }

        $this->command->info('Users seeded.');
    }
}
