<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Upload;
use App\Models\DetailUpload;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PegawaiController extends Controller
{
    public function dashboard()
    {
        $notifikasi = \App\Models\Notifikasi::where('user_id', auth()->id())->latest()->get();
        // Ambil upload aktif terbaru (belum final). Jika tidak ada, null-kan agar kartu tidak menampilkan Dipilih.
        $currentUpload = Upload::where('user_id', auth()->id())
            ->whereNotIn('status',['disetujui','ditolak'])
            ->latest('id')
            ->first();
        return view('pegawai.dashboard', compact('notifikasi','currentUpload'));
    }

    public function berkas()
    {
        $uploads = Upload::with('detailUploads')
            ->where('user_id', auth()->id())
            ->orderByDesc('id') // newest first
            ->get();
        // Group by periode preserving order of appearance (already sorted desc by id)
        $grouped = $uploads->groupBy(function($u){ return $u->periode ?: '(Tanpa Periode)'; });
        return view('pegawai.berkas', [
            'grouped' => $grouped,
        ]);
    }

    public function uploadForm()
    {
        $jenisList = array_keys(config('berkas.jenis'));
        $selectedJenis = request('jenis');
    // Ambil periode aktif dari tabel batas_waktu (jika ada)
    $aktif = \App\Models\BatasWaktu::where('aktif', true)->orderByDesc('id')->first();
    $periodeMulai = $aktif?->mulai?->toDateString();
    $periodeSelesai = $aktif?->selesai?->toDateString();
    $periodeLabel = $aktif?->label;
        // Tentukan apakah ada proses kenaikan pangkat yang belum final (tidak memiliki hasil SPK final disetujui/ditolak)
        $unfinishedUpload = Upload::where('user_id', auth()->id())
            ->whereHas('detailUploads')
            ->whereNotIn('status',['disetujui','ditolak']) // jika status sudah final, jangan kunci
            ->where(function($q){
                // hasilSpk belum ada atau belum final
                $q->whereDoesntHave('hasilSpk')
                  ->orWhereHas('hasilSpk', function($q2){
                      $q2->whereNotIn('hasil',['disetujui','ditolak']);
                  });
            })
            ->orderByDesc('id')
            ->first();
        $lockedJenis = $unfinishedUpload?->jenis; // Hanya lock jika masih ada yang belum final
        if(!$selectedJenis || !in_array($selectedJenis,$jenisList)) {
            // Coba ambil dari batch upload existing
            $existingUpload = Upload::where('user_id', auth()->id())->orderBy('id','asc')->first();
            if($existingUpload && $existingUpload->jenis) {
                $selectedJenis = $existingUpload->jenis;
            } else {
                $selectedJenis = 'reguler';
            }
        }
        // Jika ada lock dan user mencoba pilih jenis berbeda, paksa kembali ke jenis yang terkunci
        if ($lockedJenis && $selectedJenis !== $lockedJenis) {
            $selectedJenis = $lockedJenis;
        }
        // Periode default (semester atau tahun) - untuk sederhana pakai tahun sekarang
        $selectedPeriode = request('periode');
        if(!$selectedPeriode) {
            // Gunakan label periode aktif jika ada, jika tidak fallback tahun berjalan
            $selectedPeriode = $periodeLabel ?: now()->format('Y');
        }
        // Ambil daftar periode historis user untuk pemilihan ulang
        $periodeHist = Upload::where('user_id', auth()->id())
            ->select('periode')->distinct()->pluck('periode')->filter()->values();
        $requiredMap = config('berkas.jenis')[$selectedJenis] ?? [];
        // Tambahkan dokumen dinamis (docpattern_{jenis}_{slug})
        $dynamicPatterns = \App\Models\SpkSetting::where('key','like','docpattern_'.$selectedJenis.'_%')->get();
        foreach ($dynamicPatterns as $dp) {
            // key: docpattern_{jenis}_{slug}; value: pattern
            $parts = explode('_', $dp->key, 3); // [docpattern, jenis, slug]
            if (count($parts)===3) {
                $slug = $parts[2];
                // Simpan sebagai label 'Custom: {SLUG}' agar unik jika admin pakai slug langsung
                $labelCustom = str_replace('_',' ', ucfirst($slug));
                if (!array_key_exists($labelCustom, $requiredMap)) {
                    $requiredMap[$labelCustom] = $dp->value; // pattern
                }
            }
        }
        $slugToLabel = [];
        $required = collect($requiredMap)->keys()->map(function($k) use (&$slugToLabel){
            $slug = Str::slug($k,'_');
            $slugToLabel[$slug] = $k;
            return $slug; // internal key
        })->toArray();
        $labelToFile = $requiredMap; // label => filename pattern
                // Ambil detail hanya untuk kombinasi jenis + periode terpilih (agar periode baru mulai fresh)
                $details = \App\Models\DetailUpload::whereHas('upload', function($q) use ($selectedJenis,$selectedPeriode) {
                        $q->where('user_id', auth()->id())
                            ->when($selectedJenis, fn($qq)=>$qq->where('jenis',$selectedJenis))
                            ->when($selectedPeriode, fn($qq)=>$qq->where('periode',$selectedPeriode));
                })->orderBy('created_at','desc')->get();

        // Latest status per document
        $latest = $details->unique('nama_berkas')->mapWithKeys(function($item){
            return [$item->nama_berkas => $item->status];
        });

        // Documents considered already provided if status not 'ditolak'
        $existingOk = $latest->filter(fn($status)=>$status !== 'ditolak')->keys();
        $needs = collect($required)->filter(fn($r)=>!$existingOk->contains($r))->values();

        // Target pangkat options (for jenis pilihan / ijazah) with full names from pangkat table
        $targetPangkatOptions = [];
        if (in_array($selectedJenis, ['pilihan','ijazah'])) {
            $orde = config('pangkat.orde');
            $maks = config('pangkat.maks','IVe');
            $maxIdx = array_search($maks, $orde, true);
            // User current rank: try to derive canonical code from users.pangkat stored text
            $currentText = auth()->user()->pangkat; // e.g. "Penata Muda (IIIa/...)" or maybe just code
            $currentCode = null;
            // Extract pattern like IVa, IIIb etc by regex
            if (preg_match('/\b(I{1,3}V?|IV)([a-e])\b/i', $currentText, $m)) {
                $currentCode = strtoupper($m[1]).strtolower($m[2]);
            } elseif (in_array($currentText, $orde, true)) {
                $currentCode = $currentText;
            }
            $currentIdx = $currentCode ? array_search($currentCode, $orde, true) : false;
            // Load pangkat table and map to code form (golongan+ruang)
            $pangkatRows = \App\Models\Pangkat::all();
            $byCode = [];
            foreach ($pangkatRows as $row) {
                $code = strtoupper($row->golongan).strtolower($row->ruang); // e.g. IIIa
                $labelFull = $row->nama_pangkat.' ('.$row->golongan.$row->ruang.')';
                // Only keep first occurrence per code (avoid duplicates)
                if (!isset($byCode[$code])) {
                    $byCode[$code] = $labelFull;
                }
            }
            // Build allowed list strictly above current and <= maks
            foreach ($orde as $idx => $code) {
                if ($maxIdx !== false && $idx > $maxIdx) break;
                if ($currentIdx !== false && $idx <= $currentIdx) continue; // skip below or equal current
                if (isset($byCode[$code])) {
                    $targetPangkatOptions[$code] = $byCode[$code];
                }
            }
        }

        $selectedTarget = null;
        if (in_array($selectedJenis,['pilihan','ijazah'])) {
            $existingBatch = Upload::where('user_id',auth()->id())
                ->where('jenis',$selectedJenis)
                ->where('periode',$selectedPeriode)
                ->orderBy('id','asc')->first();
            $selectedTarget = $existingBatch?->target_pangkat;
        }
        return view('pegawai.upload', [
            'required' => $required,
            'needs' => $needs,
            'latest' => $latest,
            'jenisList' => $jenisList,
            'selectedJenis' => $selectedJenis,
            'selectedPeriode' => $selectedPeriode,
            'periodeHist' => $periodeHist,
            'labelToFile' => $labelToFile,
            'slugToLabel' => $slugToLabel,
            'lockedJenis' => $lockedJenis,
            'periodeMulai' => $periodeMulai,
            'periodeSelesai' => $periodeSelesai,
            'periodeLabel' => $periodeLabel,
            'targetPangkatOptions' => $targetPangkatOptions,
            'selectedTarget' => $selectedTarget,
        ]);
    }

    public function uploadStore(Request $request)
    {
        $rules = [];
        $jenisList = array_keys(config('berkas.jenis'));
        $jenis = $request->input('jenis');
        $periode = trim($request->input('periode')); // contoh: 2025-S1 atau 2025
        if(!$periode) {
            $aktif = \App\Models\BatasWaktu::where('aktif', true)->orderByDesc('id')->first();
            $periode = $aktif?->label ?: now()->format('Y');
        }
    if(strlen($periode) > 30) { return back()->withErrors(['periode'=>'Periode terlalu panjang']); }
        // Lock enforcement: user hanya boleh satu proses aktif (tanpa hasil final) sekaligus
        $unfinishedOther = Upload::where('user_id', auth()->id())
            ->whereHas('detailUploads')
            ->whereNotIn('status',['disetujui','ditolak'])
            ->where(function($q){
                $q->whereDoesntHave('hasilSpk')
                  ->orWhereHas('hasilSpk', function($q2){
                      $q2->whereNotIn('hasil',['disetujui','ditolak']);
                  });
            })
            ->when($jenis, fn($q)=>$q->where('jenis','!=',$jenis))
            ->exists();
        if ($unfinishedOther) {
            return back()->withErrors(['jenis'=>'Masih ada proses kenaikan pangkat aktif yang belum selesai (belum hasil final). Selesaikan dulu sebelum memulai yang baru.']);
        }
        if(!$jenis || !in_array($jenis,$jenisList)) {
            return back()->withErrors(['jenis'=>'Jenis kenaikan pangkat tidak valid.']);
        }
        $requiredMap = config('berkas.jenis')[$jenis]; // label => pattern name (with NIPBARU)
        // Target pangkat validation for pilihan/ijazah (persisted once)
        $targetPangkat = null;
        if (in_array($jenis, ['pilihan','ijazah'])) {
            // Check existing batch target first
            $existingBatch = Upload::where('user_id', auth()->id())
                ->where('jenis',$jenis)
                ->where('periode',$periode)
                ->orderBy('id','asc')->first();
            if ($existingBatch && $existingBatch->target_pangkat) {
                $targetPangkat = $existingBatch->target_pangkat; // lock reuse
            }
            $orde = config('pangkat.orde');
            $maks = config('pangkat.maks','IVe');
            $maxIdx = array_search($maks, $orde, true);
            $currentText = auth()->user()->pangkat;
            $currentCode = null;
            if (preg_match('/\b(I{1,3}V?|IV)([a-e])\b/i', $currentText, $m)) {
                $currentCode = strtoupper($m[1]).strtolower($m[2]);
            } elseif (in_array($currentText, $orde, true)) {
                $currentCode = $currentText;
            }
            $currentIdx = $currentCode ? array_search($currentCode, $orde, true) : false;
            // Build allowed list again (must match form generation)
            $allowed = [];
            foreach ($orde as $idx=>$code) {
                if ($maxIdx !== false && $idx > $maxIdx) break;
                if ($currentIdx !== false && $idx <= $currentIdx) continue;
                $allowed[] = $code;
            }
            if (!$targetPangkat) { // only validate if not already locked
                $target = $request->input('target_pangkat');
                if (!$target) {
                    return back()->withErrors(['target_pangkat'=>'Target pangkat wajib diisi untuk jenis '.ucfirst($jenis)])->withInput();
                }
                if (!in_array($target, $allowed, true)) {
                    return back()->withErrors(['target_pangkat'=>'Target pangkat tidak valid atau tidak berada di atas pangkat saat ini.'])->withInput();
                }
                $targetPangkat = $target;
            }
        }
        // Merge dynamic patterns for this jenis
        $dynamicPatterns = \App\Models\SpkSetting::where('key','like','docpattern_'.$jenis.'_%')->get();
        foreach ($dynamicPatterns as $dp) {
            $parts = explode('_', $dp->key, 3);
            if (count($parts)===3) {
                $slug = $parts[2];
                $labelCustom = str_replace('_',' ', ucfirst($slug));
                if (!array_key_exists($labelCustom, $requiredMap)) {
                    $requiredMap[$labelCustom] = $dp->value;
                }
            }
        }
        // Build slug => pattern map
        $slugToPattern = [];
        $required = collect($requiredMap)->keys()->map(function($k) use (&$slugToPattern,$requiredMap){
            $slug = Str::slug($k,'_');
            $slugToPattern[$slug] = $requiredMap[$k];
            return $slug;
        })->toArray();
        foreach ($required as $doc) {
            $rules["berkas.$doc"] = 'nullable|file|mimes:pdf,jpg,jpeg,png';
        }
        $validated = $request->validate($rules);

        // Validasi periode aktif jika ada
        $aktif = \App\Models\BatasWaktu::where('aktif', true)->orderByDesc('id')->first();
        if ($aktif && $aktif->mulai && $aktif->selesai) {
            $nowDate = now()->startOfDay();
            if ($nowDate->lt($aktif->mulai->startOfDay()) || $nowDate->gt($aktif->selesai->endOfDay())) {
                return back()->withErrors(['periode'=>'Di luar rentang periode pengisian aktif ('.$aktif->mulai->toDateString().' s/d '.$aktif->selesai->toDateString().').'])->withInput();
            }
        }

        // Reuse existing upload batch (first created) so all documents stay grouped
        // Gunakan kombinasi user+jenis+periode untuk batch unik (log terpisah)
        $upload = Upload::where('user_id', auth()->id())
            ->where('jenis',$jenis)
            ->where('periode',$periode)
            ->orderBy('id','asc')
            ->first();
        if(!$upload) {
            $upload = Upload::create([
                'user_id' => auth()->id(),
                'jenis' => $jenis,
                'periode' => $periode,
                'tanggal_upload' => now(),
                'status' => 'pending',
                'target_pangkat' => $targetPangkat,
            ]);
        } elseif ($targetPangkat && in_array($jenis,['pilihan','ijazah']) && !$upload->target_pangkat) {
            $upload->target_pangkat = $targetPangkat;
            $upload->save();
        }

        if ($request->has('berkas')) {
                $createdOrUpdated = [];
            foreach ($required as $doc) {
                if ($request->file("berkas.$doc")) {
                    $file = $request->file("berkas.$doc");
                    $pattern = $slugToPattern[$doc] ?? (Str::upper($doc).'_NIPBARU.'.$file->getClientOriginalExtension());
                    // Replace placeholder NIPBARU dengan ID user (sekarang berisi NIP)
                    $baseName = str_replace('NIPBARU', auth()->id(), $pattern);
                    // Split extension
                    $ext = pathinfo($baseName, PATHINFO_EXTENSION);
                    $nameWithoutExt = $ext ? substr($baseName, 0, - (strlen($ext)+1)) : $baseName;
                    // Sanitize periode & jenis for filename
                    $periodeSafe = isset($periode) ? preg_replace('/[^A-Za-z0-9_-]+/','', $periode) : 'periode';
                    $jenisSafe = Str::slug($jenis, '_');
                    // Append periode & jenis tag
                    $nameWithoutExt .= '_'.$periodeSafe.'_'.$jenisSafe;
                    if (!$ext) {
                        $ext = strtolower($file->getClientOriginalExtension());
                    }
                    $finalName = $nameWithoutExt.'.'.$ext;
                    $directory = 'berkas/'.auth()->id();
                    $stored = Storage::disk('local')->putFileAs($directory, $file, $finalName);
                    $path = $stored; // relative path stored

                    // Cari detail existing untuk dokumen ini dalam batch upload
                    $existing = DetailUpload::where('upload_id', $upload->id)
                        ->where('nama_berkas', $doc)
                        ->first();

                    if ($existing) {
                        // Hanya izinkan penggantian jika status sebelumnya ditolak
                        if ($existing->status === 'ditolak') {
                            // (Opsional) hapus file lama
                                if ($existing->path_berkas && Storage::disk('local')->exists($existing->path_berkas)) {
                                    Storage::disk('local')->delete($existing->path_berkas);
                            }
                            $existing->update([
                                'path_berkas' => $path,
                                'status' => 'pending',
                                'catatan' => null,
                                'hash' => bin2hex(random_bytes(16)),
                            ]);
                            $createdOrUpdated[] = $doc;
                        }
                        // Jika status bukan ditolak (valid/pending) abaikan agar tidak menimpa
                    } else {
                        // Belum pernah diupload, buat baru
                        DetailUpload::create([
                            'upload_id' => $upload->id,
                            'nama_berkas' => $doc,
                            'path_berkas' => $path,
                            'hash' => bin2hex(random_bytes(16)),
                            'status' => 'pending',
                        ]);
                        $createdOrUpdated[] = $doc;
                    }
                }
            }
            if (!empty($createdOrUpdated)) {
                $docList = array_map(fn($d)=>strtoupper(str_replace('_',' ',$d)),$createdOrUpdated);
                $joined = implode(', ', $docList);
                // Jika terlalu panjang, potong dan ringkas jumlah
                if (strlen($joined) > 180) {
                    $joined = implode(', ', array_slice($docList,0,6)).' ... (total '.count($docList).' dokumen)';
                }
                $pesan = auth()->user()->name.' mengunggah / memperbarui: '.$joined.' (Upload #'.$upload->id.')';
                $adminIds = User::where('role','admin')->pluck('id');
                foreach($adminIds as $adminId) {
                    \App\Models\Notifikasi::create([
                        'user_id' => $adminId,
                        'pesan' => $pesan,
                        'dibaca' => false,
                    ]);
                }
            }
        }
    return redirect()->route('pegawai.dashboard')->with('success', 'Berkas berhasil disimpan / diperbarui!');
    }

    public function notifikasiMarkAll()
    {
        \App\Models\Notifikasi::where('user_id', auth()->id())
            ->where('dibaca', false)
            ->update(['dibaca' => true]);
        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }

    public function notifikasiClearAll()
    {
        \App\Models\Notifikasi::where('user_id', auth()->id())->delete();
        return back()->with('success', 'Semua notifikasi dihapus.');
    }

    public function downloadFile($detailId)
    {
        $detail = DetailUpload::findOrFail($detailId);
        // Pastikan hanya pemilik yang boleh download
        if ($detail->upload->user_id !== auth()->id()) {
            abort(403);
        }
        $path = storage_path('app/private/'.$detail->path_berkas);
        if (!file_exists($path)) abort(404);
        $filename = basename($path);
        return response()->download($path, $filename);
    }
}
