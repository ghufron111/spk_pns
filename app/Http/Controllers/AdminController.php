<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Upload;
use App\Models\DetailUpload;
use App\Models\HasilSpk;
use App\Models\SpkSetting;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function dashboard()
    {
        // Hanya notifikasi untuk admin
        $notifikasiAdmin = \App\Models\Notifikasi::where('user_id', auth()->id())->latest()->get();
        return view('admin.dashboard', compact('notifikasiAdmin'));
    }

    public function notifikasiClearAll()
    {
        \App\Models\Notifikasi::where('user_id', auth()->id())->delete();
        return back()->with('success','Semua notifikasi dihapus');
    }

    public function notifikasiMarkAndDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        if (!is_array($ids) || empty($ids)) {
            return back()->with('success','Tidak ada notifikasi dipilih');
        }
        \App\Models\Notifikasi::where('user_id', auth()->id())->whereIn('id',$ids)->delete();
        return back()->with('success', 'Notifikasi terpilih dihapus');
    }

    // Daftar semua upload untuk proses validasi (memilih pegawai -> detail)
    public function validasiIndex()
    {
        $uploads = Upload::with(['user','detailUploads'])->get();
        return view('admin.validasi_index', compact('uploads'));
    }



    public function validasi($id)
    {
        $detail = \App\Models\DetailUpload::findOrFail($id);
        $status = request('status', 'valid');
        $catatan = request('catatan', null);
        $detail->status = $status;
        $detail->catatan = $catatan;
        $detail->save();
        // Simpan feedback jika ada catatan
        if ($catatan) {
            \App\Models\Feedback::create([
                'detail_upload_id' => $detail->id,
                'catatan' => $catatan
            ]);
        }
        // Notifikasi ke pegawai jika ditolak atau ada catatan
    if (in_array($status, ['pending','ditolak']) || $catatan) {
            \App\Models\Notifikasi::create([
                'user_id' => $detail->upload->user_id,
        'pesan' => 'Berkas '.str_replace('_',' ',$detail->nama_berkas).' status: '.strtoupper($status).($catatan?" - $catatan":''),
                'dibaca' => false
            ]);
        }
        // Jika semua detail untuk upload ini sudah valid -> hitung skor awal dan tandai siap
        $upload = $detail->upload()->with('detailUploads','user')->first();
        if ($upload) {
            $weights = config('berkas.weights');
            $scorable = $upload->detailUploads->filter(fn($d)=>array_key_exists($d->nama_berkas, $weights));
            $validDocs = $scorable->filter(fn($d)=>in_array($d->status,['valid','disetujui']));
            if ($validDocs->count() >= 3) { // minimal 3 berkas bernilai
                $rawScore = $validDocs->sum(fn($d)=>$weights[$d->nama_berkas] ?? 0);
                $maxScore = array_sum($weights); // 100 total
                $percent = $maxScore ? round(($rawScore / $maxScore) * 100,2) : 0;
                HasilSpk::updateOrCreate(
                    ['upload_id'=>$upload->id],
                    ['hasil'=>'dipertimbangkan','catatan'=>'Menunggu SPK. Skor sementara: '.$rawScore.' ('.$percent.'%)']
                );
            }
        }
        
        return back()->with('success', 'Validasi berhasil!');
    }

    public function spkIndex(\Illuminate\Http\Request $request)
    {
        // Ambil hanya upload yang memenuhi minimal dokumen bernilai
        $weights = $this->getDynamicWeights(); // legacy global (dipakai jika belum ada bobot jenis)
        $totalWeight = array_sum($weights) ?: 1; // legacy total
        $minValidDocs = $this->getSetting('threshold_min_valid_docs', config('berkas.thresholds.min_valid_docs',3));

        $uploads = Upload::with(['user','detailUploads'])
            ->get()
            ->filter(function($u) use ($minValidDocs){
                $validCount = $u->detailUploads
                    ->filter(fn($d)=>in_array($d->status,['valid','disetujui']))
                    ->count();
                return $validCount >= $minValidDocs;
            })
            ->groupBy('user_id')
            ->flatMap(function($perUser){
                // Ambil upload dengan periode 'terbaru'. Periode bisa berupa string bukan tanggal murni, jadi fallback ke id terbesar.
                $sorted = $perUser->sortByDesc(function($item){
                    // Coba parse periode sebagai tanggal; jika gagal gunakan created_at/id
                    $p = $item->periode;
                    $ts = null;
                    if ($p) {
                        try { $ts = \Carbon\Carbon::parse($p)->timestamp; } catch(\Exception $e){ $ts = null; }
                    }
                    return [$ts ?? 0, $item->id];
                });
                return $sorted->take(1); // hanya periode terbaru per user
            })
            ->values();

        // Kumpulkan daftar nilai filter yang tersedia (distinct)
        $distinctJenis = $uploads->pluck('jenis')->filter()->unique()->values();
        $distinctPeriode = $uploads->pluck('periode')->filter()->unique()->values();

        $filterJenis = $request->get('jenis');
        $filterPeriode = $request->get('periode');
        if ($filterJenis) {
            $uploads = $uploads->where('jenis', $filterJenis)->values();
        }
        if ($filterPeriode) {
            $uploads = $uploads->where('periode', $filterPeriode)->values();
        }

        // Siapkan bobot per jenis untuk tampilan skor sementara
        $weightsPerJenis = [];
        foreach ($uploads->pluck('jenis')->filter()->unique() as $j) {
            $weightsPerJenis[$j] = $this->getJenisWeights($j);
        }

        $hasil = HasilSpk::whereIn('upload_id', $uploads->pluck('id'))->get();
        return view('admin.spk_index', compact('uploads','hasil','weights','weightsPerJenis','totalWeight','distinctJenis','distinctPeriode','filterJenis','filterPeriode'));
    }

    public function spkRun(Request $request)
    {
        // Implementasi SAW: tiap dokumen bernilai menjadi kriteria benefit.
        // valueFactor per status: disetujui=1.0, valid=0.9, pending=0.4, ditolak/tidak_disetujui/missing=0.
        $statusFactor = [
            'disetujui' => 1.0,
            'valid' => 0.9,
            'pending' => 0.4,
            'ditolak' => 0.0,
            'tidak_disetujui' => 0.0,
        ];
        $approvedThreshold = $this->getSetting('threshold_approved', config('berkas.thresholds.approved', 0.85));
        $considerThreshold = $this->getSetting('threshold_consider', config('berkas.thresholds.consider', 0.55));
        $minValidDocs = $this->getSetting('threshold_min_valid_docs', config('berkas.thresholds.min_valid_docs', 3));

        // Ambil kandidat (>= minValidDocs dokumen bernilai status valid/disetujui, terlepas dari jenis)
        $uploads = Upload::with('detailUploads')->get()->filter(function($u) use ($minValidDocs){
            $count = $u->detailUploads->filter(fn($d)=>in_array($d->status,['valid','disetujui']))->count();
            return $count >= $minValidDocs;
        })->values();

        // Hitung skor SAW + kumpulkan statistik status dokumen
        $scores = [];
        foreach ($uploads as $u) {
            $jenis = $u->jenis ?: 'reguler';
            $jenisWeights = $this->getJenisWeights($jenis);
            if (empty($jenisWeights)) continue; // skip jika tidak ada bobot terdefinisi
            // Sebelumnya denominator normalisasi memakai total semua bobot terdefinisi (termasuk dokumen yang belum diunggah)
            // sehingga persentase sulit mencapai 100%. Mulai sekarang kita pakai hanya bobot dokumen yang BENAR-BENAR ADA
            // (memiliki detail upload) agar jika seluruh dokumen yang diunggah telah disetujui -> skor bisa 100%.
            $totalWeightJenis = array_sum($jenisWeights) ?: 1; // total teoretis (referensi)
            $effectiveWeight = 0; // total bobot dokumen yang muncul (uploaded) apapun statusnya
            $raw = 0.0;
            $approved = $validOnly = $pending = $rejected = 0;
            $approvedList = $validList = $pendingList = $rejectedList = $missingList = [];
            foreach ($jenisWeights as $doc => $w) {
                $detail = $u->detailUploads->firstWhere('nama_berkas', $doc);
                $st = $detail? $detail->status : null;
                $factor = $statusFactor[$st] ?? 0.0;
                if ($detail) { // hanya dokumen yang ada yang dihitung ke denominator efektif
                    $effectiveWeight += $w;
                    $raw += ($factor * $w);
                }
                $label = str_replace('_',' ', $doc);
                if (!$detail) { $missingList[] = $label; continue; }
                switch ($st) {
                    case 'disetujui': $approved++; $approvedList[] = $label; break;
                    case 'valid': $validOnly++; $validList[] = $label; break;
                    case 'pending': $pending++; $pendingList[] = $label; break;
                    case 'ditolak':
                    case 'tidak_disetujui': $rejected++; $rejectedList[] = $label; break;
                }
            }
            // Normalisasi baru: gunakan effectiveWeight jika > 0, fallback ke totalWeightJenis lama
            $denom = $effectiveWeight > 0 ? $effectiveWeight : $totalWeightJenis;
            $norm = $denom ? ($raw / $denom) : 0;
            $scores[] = [
                'upload' => $u,
                'raw' => $raw,
                'norm' => $norm,
                'approved' => $approved,
                'validOnly' => $validOnly,
                'pending' => $pending,
                'rejected' => $rejected,
                'approvedList' => $approvedList,
                'validList' => $validList,
                'pendingList' => $pendingList,
                'rejectedList' => $rejectedList,
                'missingList' => $missingList,
                'totalWeightJenis' => $totalWeightJenis, // total teoretis
                'effectiveWeight' => $effectiveWeight,   // total real dipakai
                'jenisWeights' => $jenisWeights,
            ];
        }

        // Tentukan kategori berdasarkan skor normalisasi
        foreach ($scores as $row) {
            $norm = $row['norm']; // 0..1
            // Skala baru 0..100 agar mudah dipahami
            $percentScore = round($norm * 100, 2);
            $kategori = $norm >= $approvedThreshold ? 'disetujui' : ($norm >= $considerThreshold ? 'dipertimbangkan' : 'ditolak');
            $qualified = $row['approved'] + $row['validOnly'];
            $reasonParts = [];

            // Tentukan dokumen kunci berdasarkan bobot terbesar per jenis (top 4)
            $sorted = $row['jenisWeights'];
            arsort($sorted);
            $keyDocs = array_slice(array_keys($sorted),0,4);
            $missingKey = [];
            foreach ($keyDocs as $k) {
                $nice = str_replace('_',' ', $k);
                if (!in_array($nice, $row['approvedList']) && !in_array($nice,$row['validList']) && !in_array($nice,$row['pendingList']) && !in_array($nice,$row['rejectedList'])) {
                    $missingKey[] = $nice;
                }
            }

            if ($kategori === 'disetujui') {
                $reasonParts[] = 'Memenuhi kriteria kelayakan dengan dominasi dokumen utama sah.';
                if ($row['pending'] > 0) {
                    $reasonParts[] = 'Masih ada dokumen menunggu (pending) namun tidak mengurangi kelayakan akhir.';
                }
                if ($row['validOnly'] > 0) {
                    $reasonParts[] = 'Beberapa dokumen berstatus valid menunggu persetujuan final.';
                }
            } elseif ($kategori === 'dipertimbangkan') {
                $reasonParts[] = 'Memenuhi ambang minimal dokumen bernilai (≥3) tetapi belum mencapai konsistensi optimal.';
                if ($row['pending'] > 0) {
                    $reasonParts[] = 'Terdapat dokumen masih pending yang perlu ditindaklanjuti.';
                }
                if ($row['validOnly'] > 0) {
                    $reasonParts[] = 'Sebagian dokumen masih tahap valid (belum disetujui penuh).';
                }
                if (count($row['rejectedList']) > 0) {
                    $reasonParts[] = 'Ada dokumen ditolak yang menurunkan kelayakan.';
                }
                if (count($missingKey) > 0) {
                    $reasonParts[] = 'Dokumen kunci belum lengkap: '.implode(', ', $missingKey).'.';
                }
            } else { // ditolak
                if ($qualified < $minValidDocs) {
                    $reasonParts[] = 'Jumlah dokumen bernilai yang memenuhi syarat kurang dari batas minimal ('.$minValidDocs.').';
                } else {
                    $reasonParts[] = 'Kualitas keseluruhan belum memenuhi standar kelayakan.';
                }
                if (count($row['rejectedList']) > 0) {
                    $reasonParts[] = 'Dokumen ditolak: '.implode(', ', $row['rejectedList']).'.';
                }
                if ($row['pending'] > 0) {
                    $reasonParts[] = 'Masih ada dokumen pending yang belum diverifikasi.';
                }
                if (count($missingKey) > 0) {
                    $reasonParts[] = 'Dokumen kunci belum ada: '.implode(', ', $missingKey).'.';
                }
            }

            if (empty($reasonParts)) {
                $reasonParts[] = 'Evaluasi selesai.'; // fallback
            }
            $catatan = '[SKOR: '.$percentScore.' / 100] '.implode(' ', $reasonParts);
            HasilSpk::updateOrCreate(
                ['upload_id'=>$row['upload']->id],
                ['hasil'=>$kategori,'catatan'=>$catatan]
            );
        }
        return redirect()->route('admin.spk.index')->with('success','SPK dieksekusi. Hasil diperbarui.');
    }

    public function detailUpload($uploadId)
    {
        $upload = \App\Models\Upload::with(['user','detailUploads'])->findOrFail($uploadId);
        return view('admin.detail_upload', compact('upload'));
    }

    public function validasiBatch($uploadId, Request $request)
    {
    $upload = Upload::with(['user','detailUploads'])->findOrFail($uploadId);
        $statuses = $request->input('status', []);
        $catatans = $request->input('catatan', []);
        $changed = 0;
        foreach ($upload->detailUploads as $detail) {
            $newStatus = $statuses[$detail->id] ?? $detail->status;
            $newCat = $catatans[$detail->id] ?? null;
            $needsUpdate = ($newStatus !== $detail->status) || ($newCat !== $detail->catatan && $newCat !== null);
            if ($needsUpdate) {
                $detail->status = $newStatus;
                $detail->catatan = $newCat;
                $detail->save();
                $changed++;
                if (in_array($newStatus, ['pending','ditolak']) || $newCat) {
                    \App\Models\Notifikasi::create([
                        'user_id' => $upload->user_id,
                        'pesan' => 'Berkas '.str_replace('_',' ', $detail->nama_berkas).' status: '.strtoupper($newStatus).($newCat?" - $newCat":''),
                        'dibaca' => false,
                    ]);
                }
            }
        }
        // Cek kelengkapan pasca batch
        $upload->refresh();
        if ($upload->detailUploads->count()>0 && $upload->detailUploads->every(fn($d)=>$d->status==='valid')) {
            $skor = $upload->detailUploads->count() * 10;
            HasilSpk::updateOrCreate(
                ['upload_id'=>$upload->id],
                ['hasil'=>'dipertimbangkan','catatan'=>'Menunggu eksekusi SPK. Skor dasar: '.$skor]
            );
        }
        return redirect()->route('admin.upload.detail', $uploadId)->with('success', 'Batch validasi disimpan. Perubahan: '.$changed);
    }

    public function previewFile($detailId)
    {
        $detail = \App\Models\DetailUpload::findOrFail($detailId);
    // Path sekarang disimpan relatif terhadap disk 'local' (storage/app/private)
    $path = storage_path('app/private/'.$detail->path_berkas);
        if(!file_exists($path)) abort(404);
        return response()->file($path);
    }

    public function downloadFile($detailId)
    {
        $detail = \App\Models\DetailUpload::findOrFail($detailId);
        $path = storage_path('app/private/'.$detail->path_berkas);
        if(!file_exists($path)) abort(404);
        return response()->download($path, basename($path));
    }

    // Form pengaturan bobot & ambang SPK
    public function spkSettings(Request $request)
    {
        $jenisList = array_keys(config('berkas.jenis'));
        $selectedJenis = $request->input('jenis', $jenisList[0] ?? 'reguler');
        // Ambil dokumen untuk jenis terpilih
        $dokumen = [];
        foreach ((config('berkas.jenis')[$selectedJenis] ?? []) as $label => $pattern) {
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', pathinfo($pattern, PATHINFO_FILENAME)));
            $dokumen[$slug] = $label;
        }
        // Ambil bobot per jenis
        $weights = [];
        foreach ($dokumen as $slug => $label) {
            $w = $this->getSetting('weight_'.$selectedJenis.'_'.$slug, null);
            if ($w === null) {
                // fallback ke global weight
                $w = $this->getSetting('weight_'.$slug, config('berkas.weights')[$slug] ?? 0);
            }
            $weights[$slug] = $w;
        }
        $thresholds = [
            'approved' => $this->getSetting('threshold_approved', config('berkas.thresholds.approved', 0.85)),
            'consider' => $this->getSetting('threshold_consider', config('berkas.thresholds.consider', 0.55)),
            'min_valid_docs' => $this->getSetting('threshold_min_valid_docs', config('berkas.thresholds.min_valid_docs', 3)),
        ];
        return view('admin.spk_settings', compact('weights','thresholds','dokumen','selectedJenis'));
    }

    public function spkSettingsUpdate(\Illuminate\Http\Request $request)
    {
        $jenis = $request->input('jenis', 'reguler');
        $weightsInput = $request->input('weights', []);
        $approved = (float)$request->input('thresholds.approved', 0.85);
        $consider = (float)$request->input('thresholds.consider', 0.55);
        $minValidDocs = (int)$request->input('thresholds.min_valid_docs', 3);

        if ($approved > 1) { $approved = $approved / 100; }
        if ($consider > 1) { $consider = $consider / 100; }
        $approved = max(0, min(1, $approved));
        $consider = max(0, min(1, $consider));

        $errors = [];
        if ($approved <= $consider) {
            $errors[] = 'Ambang disetujui harus lebih besar dari ambang dipertimbangkan.';
        }
        if ($approved > 1 || $consider > 1) {
            $errors[] = 'Ambang harus dalam rentang 0 - 1 (atau 0 - 100% pada tampilan).';
        }
        if ($approved < 0 || $consider < 0) {
            $errors[] = 'Ambang tidak boleh negatif.';
        }
        if (!empty($errors)) {
            return back()->withErrors($errors)->withInput();
        }
        if ($minValidDocs < 1) $minValidDocs = 1;

        // Simpan bobot per jenis
        foreach ($weightsInput as $slug => $val) {
            $v = (int)$val;
            if ($v < 0) $v = 0;
            \App\Models\SpkSetting::updateOrCreate([
                'key' => 'weight_' . $jenis . '_' . $slug
            ], [
                'value' => $v
            ]);
        }
        \App\Models\SpkSetting::updateOrCreate(['key'=>'threshold_approved'], ['value'=>$approved]);
        \App\Models\SpkSetting::updateOrCreate(['key'=>'threshold_consider'], ['value'=>$consider]);
        \App\Models\SpkSetting::updateOrCreate(['key'=>'threshold_min_valid_docs'], ['value'=>$minValidDocs]);

        return redirect()->route('admin.spk.settings', ['jenis'=>$jenis])->with('success','Pengaturan SPK diperbarui.');
    }

    // === Pengaturan Periode Pengisian ===
    public function periodeSettings()
    {
    $aktif = \App\Models\BatasWaktu::where('aktif',true)->orderByDesc('id')->first();
    $periodeAktifMulai = $aktif?->mulai?->toDateString();
    $periodeAktifSelesai = $aktif?->selesai?->toDateString();
    $periodeLabel = $aktif?->label;
    return view('admin.periode_settings', compact('periodeAktifMulai','periodeAktifSelesai','periodeLabel','aktif'));
    }

    public function periodeSettingsStore(Request $request)
    {
        $data = $request->validate([
            'mulai' => 'required|date',
            'selesai' => 'required|date|after:mulai',
            'label' => 'required|string|max:50',
        ]);
        // Nonaktifkan semua yang aktif lalu buat baru sebagai aktif
        \App\Models\BatasWaktu::where('aktif',true)->update(['aktif'=>false]);
        \App\Models\BatasWaktu::create([
            'label' => $data['label'],
            'mulai' => $data['mulai'],
            'selesai' => $data['selesai'],
            'aktif' => true,
        ]);
        return redirect()->route('admin.periode.settings')->with('success','Periode pengisian diperbarui.');
    }

    // === Pengaturan Penambahan Dokumen Dinamis ===
    public function dokumenSettings()
    {
        // Ambil config jenis + bobot dinamis
        $jenisConfig = config('berkas.jenis');
        $dynamicWeights = $this->getDynamicWeights();
        // Kumpulkan daftar semua slug yg sudah ada di config maupun muncul di upload
        $allDocs = collect($dynamicWeights)->keys();
        return view('admin.dokumen_settings', [
            'jenisConfig' => $jenisConfig,
            'allDocs' => $allDocs,
            'dynamicWeights' => $dynamicWeights,
        ]);
    }

    public function dokumenSettingsStore(Request $request)
    {
        // Hapus dokumen dinamis
        if ($request->filled('_delete_slug')) {
            $slugDel = $request->input('_delete_slug');
            // Hapus weight dan semua pattern docpattern_* yang memiliki slug ini (di semua jenis)
            SpkSetting::where('key','weight_'.$slugDel)->delete();
            SpkSetting::where('key','like','docpattern_%_'.$slugDel)->delete();
            return redirect()->route('admin.dokumen.settings')->with('success','Dokumen dinamis dihapus: '.$slugDel);
        }
        // Form menambahkan dokumen baru ke salah satu jenis
        $data = $request->validate([
            'jenis' => 'required|string',
            'label' => 'required|string|max:120',
            'pattern' => 'nullable|string|max:180',
            'weight' => 'nullable|integer|min:0|max:200'
        ]);
        $jenis = $data['jenis'];
        $label = trim($data['label']);
        $pattern = $data['pattern'] ?: Str::upper(Str::slug($label,'_')).'_NIPBARU.pdf';
        $weight = $data['weight'] ?? config('berkas.default_weight',5);
        // Simpan pattern ke spk_settings dengan key: docpattern_{jenis}_{slug}
        $slug = Str::slug($label,'_');
        SpkSetting::updateOrCreate(['key'=>'docpattern_'.$jenis.'_'.$slug], ['value'=>$pattern]);
        SpkSetting::updateOrCreate(['key'=>'weight_'.$slug], ['value'=>$weight]);
        return redirect()->route('admin.dokumen.settings')->with('success','Dokumen baru ditambahkan (slug: '.$slug.').');
    }

    private function getSetting(string $key, $default=null) {
        $row = SpkSetting::where('key',$key)->first();
        return $row ? (is_numeric($row->value)? $row->value+0 : $row->value) : $default;
    }

    private function getDynamicWeights(): array
    {
        $base = config('berkas.weights');
        $result = $base;
        // Ambil semua weight_* entries
        $records = SpkSetting::where('key','like','weight_%')->get();
        foreach ($records as $rec) {
            $doc = substr($rec->key, 7); // remove 'weight_'
            if (array_key_exists($doc, $result)) {
                $result[$doc] = (int)$rec->value;
            }
        }
        // Extend with weights that didn't exist in base (new dynamic docs)
        foreach ($records as $rec) {
            $doc = substr($rec->key,7);
            if (!array_key_exists($doc,$result)) {
                $result[$doc] = (int)$rec->value;
            }
        }
        // Perluas otomatis: dokumen yang pernah muncul tapi belum punya bobot -> pakai default_weight
        $default = config('berkas.default_weight', 5);
        $allDocs = \App\Models\DetailUpload::select('nama_berkas')->distinct()->pluck('nama_berkas');
        foreach ($allDocs as $docSlug) {
            if (!array_key_exists($docSlug, $result)) {
                $result[$docSlug] = $default;
            }
        }
        return $result;
    }

    private function getJenisWeights(string $jenis): array
    {
        $map = config('berkas.jenis')[$jenis] ?? [];
        // Merge with dynamic patterns that might have been added later (docpattern_{jenis}_*)
        $extraPatterns = SpkSetting::where('key','like','docpattern_'.$jenis.'_%')->get();
        foreach ($extraPatterns as $p) {
            // key format docpattern_{jenis}_{slug}
            $parts = explode('_', $p->key, 3); // [docpattern, jenis, slug]
            if (count($parts)===3) {
                $slug = $parts[2];
                $map[$slug] = $p->value; // store slug => pattern (value pattern)
            }
        }
        if (empty($map)) return $this->getDynamicWeights(); // fallback
        // map label => pattern; kita butuh slug label sebagai key
        $dynamic = $this->getDynamicWeights();
        $default = config('berkas.default_weight',5);
        $result = [];
        foreach ($map as $label => $pattern) {
            $slug = Str::slug($label,'_');
            // jika slug ada di dynamic pakai itu, kalau tidak pakai default
            $result[$slug] = $dynamic[$slug] ?? $default;
        }
        return $result;
    }
}
