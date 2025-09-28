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

        $currentUpload = Upload::where('user_id', auth()->id())
            ->whereNotIn('status', ['disetujui', 'ditolak'])
            ->latest('id')
            ->first();

        return view('pegawai.dashboard', compact('notifikasi', 'currentUpload'));
    }

    public function berkas()
    {
        $uploads = Upload::with('detailUploads')
            ->where('user_id', auth()->id())
            ->orderByDesc('id')
            ->get();

        $grouped = $uploads->groupBy(function ($u) {
            return $u->periode ?: '(Tanpa Periode)';
        });

        return view('pegawai.berkas', [
            'grouped' => $grouped,
        ]);
    }

    public function uploadForm()
    {
        $jenisList = array_keys(config('berkas.jenis'));
        $selectedJenis = request('jenis');

        // Ambil periode aktif
        $aktif = \App\Models\BatasWaktu::where('aktif', true)->orderByDesc('id')->first();
        $periodeMulai = $aktif?->mulai?->toDateString();
        $periodeSelesai = $aktif?->selesai?->toDateString();
        $periodeLabel = $aktif?->label;

        // Cek unfinished upload untuk lock jenis
        $unfinishedUpload = Upload::where('user_id', auth()->id())
            ->whereHas('detailUploads')
            ->whereNotIn('status', ['disetujui', 'ditolak'])
            ->where(function ($q) {
                $q->whereDoesntHave('hasilSpk')
                  ->orWhereHas('hasilSpk', function ($q2) {
                      $q2->whereNotIn('hasil', ['disetujui', 'ditolak']);
                  });
            })
            ->orderByDesc('id')
            ->first();
        $lockedJenis = $unfinishedUpload?->jenis;

        // Pilih jenis default jika tidak ada
        if (!$selectedJenis || !in_array($selectedJenis, $jenisList)) {
            $existingUpload = Upload::where('user_id', auth()->id())->orderBy('id', 'asc')->first();
            if ($existingUpload && $existingUpload->jenis) {
                $selectedJenis = $existingUpload->jenis;
            } else {
                $selectedJenis = 'reguler';
            }
        }

        // Paksa jenis terkunci jika ada proses aktif
        if ($lockedJenis && $selectedJenis !== $lockedJenis) {
            $selectedJenis = $lockedJenis;
        }

        // Periode default
        $selectedPeriode = request('periode') ?: ($periodeLabel ?: now()->format('Y'));

        // Periode historis user
        $periodeHist = Upload::where('user_id', auth()->id())
            ->select('periode')->distinct()->pluck('periode')->filter()->values();

        // Build dynamic patterns & mapping (label => pattern)
        $configMap = config('berkas.jenis')[$selectedJenis] ?? [];
        $dynamicPatterns = \App\Models\SpkSetting::where('key', 'like', 'docpattern_'.$selectedJenis.'_%')->get();

        $requiredMap = [];   // label => pattern
        $slugToLabel = [];   // slug => label
        $labelToFile = [];   // label => pattern
        $slugUsed = [];

        foreach ($dynamicPatterns as $dp) {
            $parts = explode('_', $dp->key, 3);
            if (count($parts) === 3) {
                $slug = $parts[2];
                $label = collect($configMap)->search($dp->value);
                if ($label === false) {
                    $label = str_replace('_', ' ', ucfirst($slug));
                }
                if (!in_array($slug, $slugUsed)) {
                    $requiredMap[$label] = $dp->value;
                    $slugToLabel[Str::slug($label, '_')] = $label;
                    $labelToFile[$label] = $dp->value;
                    $slugUsed[] = $slug;
                }
            }
        }

        // Jika config berkas.jenis juga punya entry (label => pattern), merge (prioritas config)
        if (is_array($configMap) && !empty($configMap)) {
            foreach ($configMap as $label => $pattern) {
                if (!array_key_exists($label, $requiredMap)) {
                    $requiredMap[$label] = $pattern;
                    $slug = Str::slug($label, '_');
                    if (!isset($slugToLabel[$slug])) {
                        $slugToLabel[$slug] = $label;
                        $labelToFile[$label] = $pattern;
                    }
                }
            }
        }

        $required = array_keys($slugToLabel); // array of slugs

        // Ambil detail uploads untuk kombinasi jenis+periode agar dapat menampilkan latest status per dokumen
        $details = \App\Models\DetailUpload::whereHas('upload', function ($q) use ($selectedJenis, $selectedPeriode) {
                $q->where('user_id', auth()->id())
                  ->when($selectedJenis, fn($qq) => $qq->where('jenis', $selectedJenis))
                  ->when($selectedPeriode, fn($qq) => $qq->where('periode', $selectedPeriode));
            })->orderBy('created_at', 'desc')->get();

        $latest = $details->unique('nama_berkas')->mapWithKeys(function ($item) {
            return [$item->nama_berkas => $item->status];
        });

        $existingOk = $latest->filter(fn($status) => $status !== 'ditolak')->keys();
        $needs = collect($required)->filter(fn($r) => !$existingOk->contains($r))->values();

        // Target pangkat options untuk jenis pilihan/ijazah
        $targetPangkatOptions = [];
        if (in_array($selectedJenis, ['pilihan', 'ijazah'])) {
            $orde = config('pangkat.orde');
            $maks = config('pangkat.maks', 'IVe');
            $maxIdx = array_search($maks, $orde, true);

            $currentText = auth()->user()->pangkat ?? '';
            $currentCode = null;
            if (preg_match('/\b(IV|I{1,3})([a-e])\b/i', $currentText, $m)) {
                $currentCode = strtoupper($m[1]).strtolower($m[2]);
            } elseif (in_array($currentText, $orde, true)) {
                $currentCode = $currentText;
            }
            $currentIdx = $currentCode ? array_search($currentCode, $orde, true) : false;

            $pangkatRows = \App\Models\Pangkat::all();
            $byCode = [];
            foreach ($pangkatRows as $row) {
                $code = strtoupper($row->golongan).strtolower($row->ruang);
                $labelFull = $row->nama_pangkat.' ('.$row->golongan.$row->ruang.')';
                if (!isset($byCode[$code])) $byCode[$code] = $labelFull;
            }

            foreach ($orde as $idx => $code) {
                if ($maxIdx !== false && $idx > $maxIdx) break;
                if ($currentIdx !== false && $idx <= $currentIdx) continue;
                if (isset($byCode[$code])) {
                    $targetPangkatOptions[$code] = $byCode[$code];
                }
            }
        }

        // Selected target jika batch sudah ada
        $selectedTarget = null;
        if (in_array($selectedJenis, ['pilihan', 'ijazah'])) {
            $existingBatch = Upload::where('user_id', auth()->id())
                ->where('jenis', $selectedJenis)
                ->where('periode', $selectedPeriode)
                ->orderBy('id', 'asc')->first();
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
        // Ambil input penting
        $jenisList = array_keys(config('berkas.jenis'));
        $jenis = $request->input('jenis');
        $periode = trim($request->input('periode'));
        if (!$periode) {
            $aktif = \App\Models\BatasWaktu::where('aktif', true)->orderByDesc('id')->first();
            $periode = $aktif?->label ?: now()->format('Y');
        }
        if (strlen($periode) > 30) {
            return back()->withErrors(['periode' => 'Periode terlalu panjang'])->withInput();
        }

        // Lock enforcement: jangan biarkan user buat upload baru jika ada proses lain (kecuali jenis sama)
        $unfinishedOther = Upload::where('user_id', auth()->id())
            ->whereHas('detailUploads')
            ->whereNotIn('status', ['disetujui', 'ditolak'])
            ->where(function ($q) {
                $q->whereDoesntHave('hasilSpk')
                  ->orWhereHas('hasilSpk', function ($q2) {
                      $q2->whereNotIn('hasil', ['disetujui', 'ditolak']);
                  });
            })
            ->when($jenis, fn($q) => $q->where('jenis', '!=', $jenis))
            ->exists();

        if ($unfinishedOther) {
            return back()->withErrors(['jenis' => 'Masih ada proses kenaikan pangkat aktif yang belum selesai. Selesaikan dulu sebelum memulai yang baru.'])->withInput();
        }

        if (!$jenis || !in_array($jenis, $jenisList)) {
            return back()->withErrors(['jenis' => 'Jenis kenaikan pangkat tidak valid.'])->withInput();
        }

        // Build required map (merge config + dynamic spk settings)
        $requiredMap = config('berkas.jenis')[$jenis] ?? [];
        $dynamicPatterns = \App\Models\SpkSetting::where('key', 'like', 'docpattern_'.$jenis.'_%')->get();
        foreach ($dynamicPatterns as $dp) {
            $parts = explode('_', $dp->key, 3);
            if (count($parts) === 3) {
                $slug = $parts[2];
                $label = str_replace('_', ' ', ucfirst($slug));
                if (!array_key_exists($label, $requiredMap)) {
                    $requiredMap[$label] = $dp->value;
                }
            }
        }

        // Build slug => pattern map and required slugs list
        $slugToPattern = [];
        $required = collect($requiredMap)->keys()->map(function ($k) use (&$slugToPattern, $requiredMap) {
            $slug = Str::slug($k, '_');
            $slugToPattern[$slug] = $requiredMap[$k];
            return $slug;
        })->toArray();

        // Catatan: Kita tidak memakai $request->validate massal supaya
        // jika beberapa file gagal (ukuran/tipe), file lain tetap diproses.
        $fileErrors = [];
        $allowedExt = ['pdf', 'jpg', 'jpeg', 'png'];

        // Additional validation for target pangkat if needed
        $targetPangkat = null;
        if (in_array($jenis, ['pilihan', 'ijazah'])) {
            // Reuse existing batch target if exists
            $existingBatch = Upload::where('user_id', auth()->id())
                ->where('jenis', $jenis)
                ->where('periode', $periode)
                ->orderBy('id', 'asc')->first();
            if ($existingBatch && $existingBatch->target_pangkat) {
                $targetPangkat = $existingBatch->target_pangkat;
            }

            $orde = config('pangkat.orde');
            $maks = config('pangkat.maks', 'IVe');
            $maxIdx = array_search($maks, $orde, true);

            $currentText = auth()->user()->pangkat ?? '';
            $currentCode = null;
            if (preg_match('/\b(IV|I{1,3})([a-e])\b/i', $currentText, $m)) {
                $currentCode = strtoupper($m[1]).strtolower($m[2]);
            } elseif (in_array($currentText, $orde, true)) {
                $currentCode = $currentText;
            }
            $currentIdx = $currentCode ? array_search($currentCode, $orde, true) : false;

            $allowed = [];
            foreach ($orde as $idx => $code) {
                if ($maxIdx !== false && $idx > $maxIdx) break;
                if ($currentIdx !== false && $idx <= $currentIdx) continue;
                $allowed[] = $code;
            }

            if (!$targetPangkat) {
                $target = $request->input('target_pangkat');
                if (!$target) {
                    return back()->withErrors(['target_pangkat' => 'Target pangkat wajib diisi untuk jenis '.ucfirst($jenis)])->withInput();
                }
                if (!in_array($target, $allowed, true)) {
                    return back()->withErrors(['target_pangkat' => 'Target pangkat tidak valid atau tidak berada di atas pangkat saat ini.'])->withInput();
                }
                $targetPangkat = $target;
            }
        }

    // (Tidak ada validate massal di sini — per-file manual di loop nanti)

        // Validasi periode aktif (batas waktu)
        $aktif = \App\Models\BatasWaktu::where('aktif', true)->orderByDesc('id')->first();
        if ($aktif && $aktif->mulai && $aktif->selesai) {
            $nowDate = now()->startOfDay();
            if ($nowDate->lt($aktif->mulai->startOfDay()) || $nowDate->gt($aktif->selesai->endOfDay())) {
                return back()->withErrors(['periode' => 'Di luar rentang periode pengisian aktif ('.$aktif->mulai->toDateString().' s/d '.$aktif->selesai->toDateString().').'])->withInput();
            }
        }

        // Reuse atau buat batch upload
        $upload = Upload::where('user_id', auth()->id())
            ->where('jenis', $jenis)
            ->where('periode', $periode)
            ->orderBy('id', 'asc')
            ->first();

        if (!$upload) {
            $upload = Upload::create([
                'user_id' => auth()->id(),
                'jenis' => $jenis,
                'periode' => $periode,
                'tanggal_upload' => now(),
                'status' => 'pending',
                'target_pangkat' => $targetPangkat,
            ]);
        } elseif ($targetPangkat && in_array($jenis, ['pilihan', 'ijazah']) && !$upload->target_pangkat) {
            $upload->target_pangkat = $targetPangkat;
            $upload->save();
        }

        $createdOrUpdated = [];

        if ($request->hasFile('berkas')) {
            foreach ($required as $doc) {
                $uploaded = $request->file("berkas.$doc");
                if (!$uploaded) {
                    continue; // dokumen ini tidak diunggah pada batch ini
                }

                $size = $uploaded->getSize();
                $ext = strtolower($uploaded->getClientOriginalExtension());
                if (!in_array($ext, $allowedExt)) {
                    $fileErrors["berkas.$doc"] = 'Format file harus PDF/JPG/PNG.';
                    continue;
                }
                if ($size > 2 * 1024 * 1024) { // 2 MB
                    $fileErrors["berkas.$doc"] = 'Ukuran file maksimal 2 MB.';
                    continue;
                }

                $pattern = $slugToPattern[$doc] ?? (Str::upper($doc).'_NIPBARU.'.$ext);
                $baseName = str_replace('NIPBARU', auth()->id(), $pattern);
                $extInBase = pathinfo($baseName, PATHINFO_EXTENSION);
                $nameWithoutExt = $extInBase ? substr($baseName, 0, - (strlen($extInBase) + 1)) : $baseName;

                $periodeSafe = isset($periode) ? preg_replace('/[^A-Za-z0-9_-]+/', '', $periode) : 'periode';
                $jenisSafe = Str::slug($jenis, '_');
                $nameWithoutExt .= '_'.$periodeSafe.'_'.$jenisSafe;
                $finalName = $nameWithoutExt.'.'.$ext;

                $directory = 'berkas/'.auth()->id();
                $stored = Storage::disk('local')->putFileAs($directory, $uploaded, $finalName);
                $path = $stored; // relative path

                $existing = DetailUpload::where('upload_id', $upload->id)
                    ->where('nama_berkas', $doc)
                    ->first();

                if ($existing) {
                    if ($existing->status === 'ditolak') {
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
                } else {
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

        // Kirim notifikasi jika ada dokumen berhasil diproses
        if (!empty($createdOrUpdated)) {
            $docList = array_map(fn($d) => strtoupper(str_replace('_', ' ', $d)), $createdOrUpdated);
            $joined = implode(', ', $docList);
            if (strlen($joined) > 180) {
                $joined = implode(', ', array_slice($docList, 0, 6)).' ... (total '.count($docList).' dokumen)';
            }
            $pesan = auth()->user()->name.' mengunggah / memperbarui: '.$joined.' (Upload #'.$upload->id.')';
            $adminIds = User::where('role', 'admin')->pluck('id');
            foreach ($adminIds as $adminId) {
                \App\Models\Notifikasi::create([
                    'user_id' => $adminId,
                    'pesan' => $pesan,
                    'dibaca' => false,
                ]);
            }
        }

        // Jika ada error sebagian, kembali ke form supaya user tahu file mana gagal
        if (!empty($fileErrors)) {
            $successMsg = !empty($createdOrUpdated)
                ? ('Sebagian berkas berhasil diunggah: '.count($createdOrUpdated).' dokumen. Silakan perbaiki yang gagal.')
                : 'Tidak ada berkas yang berhasil diunggah.';
            return back()->withErrors($fileErrors)->with('success', $successMsg);
        }

        if (empty($createdOrUpdated)) {
            return back()->with('info', 'Tidak ada berkas baru yang dipilih.');
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

        if ($detail->upload->user_id !== auth()->id()) {
            abort(403);
        }

        // path relatif disimpan (mis. berkas/{id}/filename.pdf)
        $relativePath = $detail->path_berkas;
        $fullPath = storage_path('app/'.$relativePath);

        if (!file_exists($fullPath)) {
            abort(404);
        }

        $filename = basename($fullPath);
        return response()->download($fullPath, $filename);
    }
}
