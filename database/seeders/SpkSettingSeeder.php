<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SpkSetting;

class SpkSettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'weight_surat_pengantar' => 15,
            'weight_sk_pangkat_terakhir' => 25,
            'weight_sk_jabatan_terakhir' => 20,
            'weight_skp_dua_tahun_terakhir' => 20,
            'weight_karpeg' => 5,
            'weight_drh' => 5,
            'weight_pernyataan_disiplin' => 10,
            'threshold_approved' => 0.85,
            'threshold_consider' => 0.55,
            'threshold_min_valid_docs' => 3,
        ];
        foreach ($defaults as $k=>$v) {
            SpkSetting::updateOrCreate(['key'=>$k], ['value'=>$v]);
        }
    }
}
