<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Tampilkan profil user saat ini.
     */
    public function show()
    {
        $user = auth()->user();
        return view('profile.show', compact('user'));
    }

    /**
     * Form edit profil.
     */
    public function edit()
    {
        $user = auth()->user();
        return view('profile.edit', compact('user'));
    }

    /**
     * Update data profil (nama, email, avatar).
     * Catatan:
     *  - Email divalidasi unique dengan mengabaikan user saat ini agar tidak kena duplicate.
     *  - Avatar lama dihapus hanya setelah upload baru berhasil disimpan.
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        // Validasi. Unique email diabaikan untuk ID user saat ini supaya tidak error saat tidak mengubah email.
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        $dirty = false; // tracker apakah ada perubahan sehingga kita tidak melakukan save sia-sia.

        if ($user->name !== $data['name']) {
            $user->name = $data['name'];
            $dirty = true;
        }
        if ($user->email !== $data['email']) {
            $user->email = $data['email'];
            $dirty = true;
        }

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            // Direktori: private/{username}/profil
                // Gunakan identifier unik: prioritas nip (jika field tersedia di tabel) else id user.
                $identifier = $user->nip ?? ('id'.$user->id);
                $dir = 'users/' . $identifier . '/profil';
            $filename = 'avatar_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs($dir, $filename, 'local'); // disk 'local' mengarah ke storage/app/private

            // Hapus avatar lama (pindah dari disk manapun). Cek di disk local terlebih dahulu.
            if ($user->avatar_path) {
                if (Storage::disk('local')->exists($user->avatar_path)) {
                    Storage::disk('local')->delete($user->avatar_path);
                } elseif (Storage::disk('public')->exists($user->avatar_path)) {
                    // Legacy location (sebelumnya di public) - bersihkan juga
                    Storage::disk('public')->delete($user->avatar_path);
                }
            }
            $user->avatar_path = $path; // Simpan relative path (tanpa /private) karena root disk 'local' sudah di app/private
            $dirty = true;
        }

        // Jika user melakukan cropping (base64) kirimkan melalui avatar_cropped; prioritas lebih tinggi dari file asli.
        if ($request->filled('avatar_cropped')) {
            $base64 = $request->input('avatar_cropped');
            if (preg_match('/^data:image\/(png|jpe?g);base64,/', $base64, $m)) {
                $ext = $m[1] === 'jpeg' ? 'jpg' : $m[1];
                $data = substr($base64, strpos($base64, ',')+1);
                $bin = base64_decode($data);
                if ($bin !== false) {
                    $identifier = $user->nip ?? ('id'.$user->id);
                    $dir = 'users/' . $identifier . '/profil';
                    $filename = 'avatar_' . time() . '_crop.' . $ext;
                    $path = $dir . '/' . $filename;
                    // Hapus avatar lama
                    if ($user->avatar_path) {
                        if (Storage::disk('local')->exists($user->avatar_path)) {
                            Storage::disk('local')->delete($user->avatar_path);
                        } elseif (Storage::disk('public')->exists($user->avatar_path)) {
                            Storage::disk('public')->delete($user->avatar_path);
                        }
                    }
                    Storage::disk('local')->put($path, $bin);
                    $user->avatar_path = $path;
                    $dirty = true;
                }
            }
        }

        if ($dirty) {
            $user->save();
        }

        return redirect()->route('profile.show')->with('success', 'Profil diperbarui');
    }

    /**
     * Stream avatar privat user saat ini.
     * Hanya user sendiri yang bisa mengakses (route dilindungi auth).
     */
    public function avatar()
    {
        $user = auth()->user();
        if (!$user->avatar_path || !Storage::disk('local')->exists($user->avatar_path)) {
            abort(404);
        }
        $mime = Storage::disk('local')->mimeType($user->avatar_path) ?: 'image/png';
        $stream = Storage::disk('local')->get($user->avatar_path);
        return response($stream, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'max-age=3600, public'
        ]);
    }
}
