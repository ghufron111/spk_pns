<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PasswordController extends Controller
{
    public function edit(){
        return view('profile.password');
    }
    public function update(Request $request){
        $data = $request->validate([
            'password' => ['required','min:6','regex:/[A-Z]/','regex:/[a-z]/','regex:/[0-9]/','confirmed']
        ]);
        $user = auth()->user();
        $user->password = Hash::make($data['password']);
        $user->save();
        return back()->with('success','Password berhasil diubah');
    }
}
