<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Upload;
use App\Models\DetailUpload;
use Illuminate\Support\Str;

class SampleUploadSeeder extends Seeder
{
    public function run(): void
    {
        $userId = 1; // asumsi user factory pertama (Test User) atau ganti sesuai kebutuhan
        if(!\App\Models\User::find($userId)) return; // skip jika tidak ada
        $jenis = 'reguler';
        $periode = now()->format('Y');
        $upload = Upload::create([
            'user_id' => $userId,
            'jenis' => $jenis,
            'periode' => $periode,
            'tanggal_upload' => now(),
            'status' => 'pending',
        ]);
        $docs = [ 'sk_cpns','sk_pns','sk_kenaikan_pangkat_terakhir' ];
        foreach($docs as $d) {
            DetailUpload::create([
                'upload_id' => $upload->id,
                'nama_berkas' => $d,
                'path_berkas' => 'berkas/'.$userId.'/'.Str::upper($d).'_'.$userId.'_'.$periode.'_'.$jenis.'.pdf',
                'hash' => bin2hex(random_bytes(16)),
                'status' => 'pending',
            ]);
        }
    }
}
