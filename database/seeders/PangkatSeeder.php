<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PangkatSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('pangkat')->insert([
            // GOLONGAN IV (Pembina)
            ['nama_pangkat' => 'Pembina Utama', 'golongan' => 'IV', 'ruang' => 'e'],
            ['nama_pangkat' => 'Pembina Utama Madya', 'golongan' => 'IV', 'ruang' => 'd'],
            ['nama_pangkat' => 'Pembina Utama Muda', 'golongan' => 'IV', 'ruang' => 'c'],
            ['nama_pangkat' => 'Pembina Tingkat I', 'golongan' => 'IV', 'ruang' => 'b'],
            ['nama_pangkat' => 'Pembina', 'golongan' => 'IV', 'ruang' => 'a'],
            // GOLONGAN III (Penata)
            ['nama_pangkat' => 'Penata Tingkat I', 'golongan' => 'III', 'ruang' => 'd'],
            ['nama_pangkat' => 'Penata', 'golongan' => 'III', 'ruang' => 'c'],
            ['nama_pangkat' => 'Penata Muda Tingkat I', 'golongan' => 'III', 'ruang' => 'b'],
            ['nama_pangkat' => 'Penata Muda', 'golongan' => 'III', 'ruang' => 'a'],
            // GOLONGAN II (Pengatur)
            ['nama_pangkat' => 'Pengatur Tingkat I', 'golongan' => 'II', 'ruang' => 'd'],
            ['nama_pangkat' => 'Pengatur', 'golongan' => 'II', 'ruang' => 'c'],
            ['nama_pangkat' => 'Pengatur Muda Tingkat I', 'golongan' => 'II', 'ruang' => 'b'],
            ['nama_pangkat' => 'Pengatur Muda', 'golongan' => 'II', 'ruang' => 'a'],
            // GOLONGAN I (Juru)
            ['nama_pangkat' => 'Juru Tingkat I', 'golongan' => 'I', 'ruang' => 'd'],
            ['nama_pangkat' => 'Juru', 'golongan' => 'I', 'ruang' => 'c'],
            ['nama_pangkat' => 'Juru Muda Tingkat I', 'golongan' => 'I', 'ruang' => 'b'],
            ['nama_pangkat' => 'Juru Muda', 'golongan' => 'I', 'ruang' => 'a'],
        ]);
    }
}
