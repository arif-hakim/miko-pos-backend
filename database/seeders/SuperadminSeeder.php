<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class SuperadminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $admin = User::create([
            'firstname' => 'Super',
            'lastname' => 'Administrator',
            'email' => 'admin@miko-pos.id',
            'password' => bcrypt('admin123'),
            'address' => 'Jl. Jaksanaranata no. 36',
            'phone' => '082127425764',
            'role' => 'Admin',
        ]);
    }
}
