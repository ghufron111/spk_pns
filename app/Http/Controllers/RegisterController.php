<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function show()
    {
    $pangkatList = \App\Models\Pangkat::orderBy('golongan')->orderBy('ruang')->orderBy('nama_pangkat')->get();
    return view('auth.register', compact('pangkatList'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string|max:32|unique:users,id',
            'name' => 'required|string|max:120',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'pangkat_id' => 'required|exists:pangkat,id',
        ]);
        $pangkat = \App\Models\Pangkat::find($data['pangkat_id']);
        $user = User::create([
            'id' => $data['id'],
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'pegawai',
            'pangkat' => $pangkat ? ($pangkat->nama_pangkat.' ('.$pangkat->golongan.'/'.$pangkat->ruang.')') : null,
            'pangkat_id' => $pangkat?->id,
        ]);
        Auth::login($user);
        return redirect()->route('pegawai.dashboard');
    }
}
