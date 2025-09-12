<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => ['required','string'],
            'password' => ['required','string'],
        ]);
        $login = trim($data['login']);
        $password = $data['password'];

        // Coba login dengan email dahulu
        $byEmail = filter_var($login, FILTER_VALIDATE_EMAIL) ? ['email'=>$login, 'password'=>$password] : null;
        if ($byEmail && Auth::attempt($byEmail)) {
            $request->session()->regenerate();
            $role = Auth::user()->role;
            if ($role === 'admin') {
                return redirect()->route('admin.dashboard');
            } elseif ($role === 'pimpinan') {
                return redirect()->route('pimpinan.dashboard');
            } else {
                return redirect()->route('pegawai.dashboard');
            }
        }

        // Jika bukan email atau gagal, coba sebagai NIP pada kolom id
        $nip = preg_replace('/\D+/','', $login); // ambil digit saja
        if ($nip !== '' && Auth::attempt(['id'=>$nip, 'password'=>$password])) {
            $request->session()->regenerate();
            $role = Auth::user()->role;
            if ($role === 'admin') {
                return redirect()->route('admin.dashboard');
            } elseif ($role === 'pimpinan') {
                return redirect()->route('pimpinan.dashboard');
            } else {
                return redirect()->route('pegawai.dashboard');
            }
        }

        return back()->withErrors([
            'login' => 'Email/NIP atau password salah.',
        ])->onlyInput('login');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
