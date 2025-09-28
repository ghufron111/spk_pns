<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Upload;
use App\Models\HasilSpk;
use App\Models\Pangkat;

class PimpinanController extends Controller
{
    private function parseRankCode($user, $orde){
        // 1. From pangkat_id
        if ($user->pangkat_id && ($p = Pangkat::find($user->pangkat_id))) {
            return strtoupper($p->golongan).strtolower($p->ruang); // IVe
        }
        $str = $user->pangkat;
        if ($str) {
            // Pattern with slash or parenthesis e.g. "Pembina Utama (IV/e)" or "(III/b)"
            if (preg_match('/(I{1,3}|IV)\s*[\/-]?\s*([a-eA-E])/', $str, $m)) {
                return strtoupper($m[1]).strtolower($m[2]);
            }
            // Direct clean code present
            if (in_array($str,$orde,true)) return $str;
        }
        return null;
    }

    public function dashboard()
    {
    $totalPegawai = \App\Models\User::where('role','pegawai')->count();
    $totalDipertimbangkan = Upload::whereHas('hasilSpk',fn($q)=>$q->where('hasil','dipertimbangkan'))->count();
    $totalDisetujui = Upload::whereHas('hasilSpk',fn($q)=>$q->where('hasil','disetujui'))->count();
    $totalDitolak = Upload::whereHas('hasilSpk',fn($q)=>$q->where('hasil','ditolak'))->count();
    $totalUploadAktif = Upload::whereNotIn('status',['disetujui','ditolak'])->count();
    return view('pimpinan.dashboard', compact('totalPegawai','totalDipertimbangkan','totalDisetujui','totalDitolak','totalUploadAktif'));
    }

    // Daftar seluruh hasil SPK yang siap keputusan final (hasil_spk kategori dipertimbangkan / disetujui)
    public function approvals(\Illuminate\Http\Request $request)
    {
        $filterJenis = $request->get('jenis');
        // Halaman REKOMENDASI: hanya tampilkan hasilSpk kategori 'disetujui' (rekomendasi SPK)
        // yang BELUM difinalkan (upload.status bukan disetujui/ditolak). Pimpinan tetap harus memutuskan.
        $query = Upload::with(['user','hasilSpk'])
            ->whereNotIn('status',[ 'disetujui','ditolak'])
            ->whereHas('hasilSpk', function($q){
                $q->where('hasil','disetujui');
            });
        if ($filterJenis) { $query->where('jenis',$filterJenis); }
        $uploads = $query->orderByDesc('id')->get()
            ->groupBy('user_id')
            ->flatMap(function($perUser){
                $sorted = $perUser->sortByDesc(function($item){
                    $p = $item->periode; $ts=null; try { if($p) $ts=\Carbon\Carbon::parse($p)->timestamp; } catch(\Exception $e) { $ts=null; }
                    return [$ts ?? 0, $item->id];
                });
                return $sorted->take(1);
            })
            ->values();
        $distinctJenis = Upload::select('jenis')->distinct()->pluck('jenis')->filter();
        $mode = 'rekomendasi';
        return view('pimpinan.approvals', compact('uploads','filterJenis','distinctJenis','mode'));
    }

    public function considerations(Request $request)
    {
        $filterJenis = $request->get('jenis');
        $query = Upload::with(['user','hasilSpk'])
            ->whereNotIn('status',['disetujui','ditolak'])
            ->whereHas('hasilSpk', fn($q)=>$q->where('hasil','dipertimbangkan'));
        if ($filterJenis) { $query->where('jenis',$filterJenis); }
        $uploads = $query->orderByDesc('id')->get()
            ->groupBy('user_id')
            ->flatMap(function($perUser){
                $sorted = $perUser->sortByDesc(function($item){
                    $p = $item->periode; $ts=null; try { if($p) $ts=\Carbon\Carbon::parse($p)->timestamp; } catch(\Exception $e) { $ts=null; }
                    return [$ts ?? 0, $item->id];
                });
                return $sorted->take(1);
            })
            ->values();
        $distinctJenis = Upload::select('jenis')->distinct()->pluck('jenis')->filter();
        $mode = 'dipertimbangkan';
        return view('pimpinan.considerations', compact('uploads','filterJenis','distinctJenis','mode'));
    }

    public function approve($id)
    {
        $upload = Upload::with('hasilSpk','user')->findOrFail($id);
        $upload->status = 'disetujui';
        $upload->save();
        if ($upload->hasilSpk) {
            $upload->hasilSpk->hasil = 'disetujui';
            $upload->hasilSpk->catatan = trim(($upload->hasilSpk->catatan ?? '').' [Disetujui pimpinan]');
            $upload->hasilSpk->save();
        }

        $user = $upload->user;
        $orde = config('pangkat.orde');
        $maks = config('pangkat.maks','IVe');
        $currentCode = $this->parseRankCode($user,$orde);
        $currentIdx = $currentCode !== null ? array_search($currentCode,$orde,true) : false;
        $maxIdx = array_search($maks,$orde,true);
        $newRank = null;
        if ($upload->jenis === 'reguler') {
            if ($currentIdx !== false) {
                $nextIdx = $currentIdx + 1;
                if ($maxIdx !== false) $nextIdx = min($nextIdx,$maxIdx);
                if ($nextIdx < count($orde) && $nextIdx > $currentIdx) $newRank = $orde[$nextIdx];
            }
        } elseif (in_array($upload->jenis,['pilihan','ijazah'])) {
            $target = $upload->target_pangkat;
            if ($target) {
                $targetIdx = array_search($target,$orde,true);
                if ($targetIdx !== false && ($currentIdx === false || $targetIdx > $currentIdx)) {
                    if ($maxIdx !== false && $targetIdx > $maxIdx) $targetIdx = $maxIdx;
                    $newRank = $orde[$targetIdx];
                }
            }
        }
        if ($newRank && $newRank !== $currentCode) {
            $user->pangkat = $newRank;
            if (preg_match('/^(I{1,3}|IV)([a-e])$/i', $newRank, $m)) {
                $gol = strtoupper($m[1]); $ru = strtolower($m[2]);
                if ($row = Pangkat::where('golongan',$gol)->where('ruang',$ru)->first()) $user->pangkat_id = $row->id;
            }
            $user->save();
        }
        \App\Models\Notifikasi::create([
            'user_id' => $upload->user_id,
            'pesan' => 'Pengajuan kenaikan pangkat (jenis: '.strtoupper($upload->jenis).', periode: '.$upload->periode.') DISETUJUI pimpinan.' . ($newRank ? ' Pangkat baru: '.$newRank : ''),
            'dibaca' => false,
        ]);
        return back()->with('success','Upload #'.$upload->id.' disetujui.' . ($newRank ? ' Pangkat naik ke '.$newRank : ''));
    }

    public function reject($id)
    {
        $upload = Upload::with('hasilSpk','user')->findOrFail($id);
        $upload->status = 'ditolak';
        $upload->save();
        if ($upload->hasilSpk) {
            $upload->hasilSpk->hasil = 'ditolak';
            $upload->hasilSpk->catatan = trim(($upload->hasilSpk->catatan ?? '').' [Ditolak pimpinan]');
            $upload->hasilSpk->save();
        }
        \App\Models\Notifikasi::create([
            'user_id' => $upload->user_id,
            'pesan' => 'Pengajuan kenaikan pangkat (jenis: '.strtoupper($upload->jenis).', periode: '.$upload->periode.') DITOLAK pimpinan.',
            'dibaca' => false,
        ]);
        return back()->with('success','Upload #'.$upload->id.' ditolak.');
    }

    public function validasi($id)
    {
        $upload = \App\Models\Upload::findOrFail($id);
        // Pimpinan memilih status final: disetujui atau ditolak
        $upload->status = request('status', 'disetujui');
        $upload->save();
        return back()->with('success', 'Keputusan final telah disimpan!');
    }

    public function pegawaiIndex(Request $request)
    {
        $q = $request->get('q');
        $usersQuery = \App\Models\User::with('pangkatRef')->where('role','pegawai');
        if($q){
            $usersQuery->where(function($sub) use ($q){
                $sub->where('id','like',"%$q%")
                     ->orWhere('name','like',"%$q%");
            });
        }
        $users = $usersQuery->orderBy('name')->paginate(25)->withQueryString();

        // Ambil riwayat kenaikan dari uploads yang disetujui
        $uploadApproved = Upload::whereIn('user_id', $users->pluck('id'))
            ->where('status','disetujui')
            ->with('hasilSpk')
            ->orderBy('tanggal_upload','desc')
            ->get()
            ->groupBy('user_id');

        // Mapping ke struktur ringkas
        $history = [];
        foreach($uploadApproved as $uid => $coll){
            $jumlah = $coll->count();
            $terakhir = $coll->first();
            $history[$uid] = [
                'count' => $jumlah,
                'last_periode' => $terakhir->periode ?? null,
                'last_jenis' => $terakhir->jenis ?? null,
                'last_target' => $terakhir->target_pangkat ?? null,
            ];
        }
        return view('pimpinan.pegawai', compact('users','q','history'));
    }
}
