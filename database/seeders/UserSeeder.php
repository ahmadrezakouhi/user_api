<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::factory(10)->create();
        User::create([
            'name'=>'admin',
            'email'=>'admin@gmail.com',
            'phone'=>'09130774939',
            'country'=>'iran',
            'city'=>'tehran',
            'address'=>'azadi',
            'is_admin'=>1,
            'user_expire_date'=>null,
            'user_mode'=>1,
            'last_active_date'=>null,
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password

        ]);
    }
}
