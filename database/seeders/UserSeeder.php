<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pimpinan',
                'email' => 'pimpinan@example.com',
                'password' => Hash::make('password'),
                'role' => 'pimpinan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
