<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SpkSetting;

class SpkSettingsSeeder extends Seeder
{
    public function run()
    {
        $jenisList = config('berkas.jenis');
        $defaultWeights = config('berkas.weights');
        $defaultWeight = config('berkas.default_weight', 5);
        foreach ($jenisList as $jenis => $docs) {
            foreach ($docs as $label => $pattern) {
                $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', pathinfo($pattern, PATHINFO_FILENAME)));
                // Simpan pattern
                SpkSetting::updateOrCreate([
                    'key' => 'docpattern_' . $jenis . '_' . $slug
                ], [
                    'value' => $pattern
                ]);
                // Simpan bobot default jika belum ada
                $weight = $defaultWeights[$slug] ?? $defaultWeight;
                SpkSetting::updateOrCreate([
                    'key' => 'weight_' . $jenis . '_' . $slug
                ], [
                    'value' => $weight
                ]);
            }
        }
    }
}
