<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Urutan seeding: Users (default), Pangkat, SPK Settings, Sample Upload (optional)
        $this->call([
            UserSeeder::class,
            PangkatSeeder::class,
            SpkSettingSeeder::class,
            SampleUploadSeeder::class,
        ]);
    }
}
