<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    private array $roles = ['admin','pegawai','pimpinan'];

    public function index(Request $request)
    {
        $role = $request->get('role');
        $query = User::query();
        if ($role && in_array($role, $this->roles)) {
            $query->where('role',$role);
        }
        // Urutkan berdasarkan ID (terbaru dulu)
        $users = $query
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();
        return view('admin.users.index', [
            'users' => $users,
            'roles' => $this->roles,
            'filterRole' => $role,
        ]);
    }

    public function create()
    {
        return view('admin.users.create', [
            'roles' => $this->roles,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|unique:users,email',
            'nip' => 'nullable|string|max:30|unique:users,nip',
            'role' => ['required', Rule::in($this->roles)],
            'password' => 'required|min:6|confirmed|regex:/[A-Z]/|regex:/[a-z]/|regex:/[0-9]/',
        ]);
        $user = new User();
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->nip = $data['nip'] ?? null;
        $user->role = $data['role'];
        $user->password = Hash::make($data['password']);
        $user->save();
        return redirect()->route('admin.users.index')->with('success','User berhasil dibuat');
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', [
            'user' => $user,
            'roles' => $this->roles,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => ['required','email', Rule::unique('users','email')->ignore($user->id)],
            'nip' => ['nullable','string','max:30', Rule::unique('users','nip')->ignore($user->id)],
            'role' => ['required', Rule::in($this->roles)],
            'password' => 'nullable|min:6|confirmed|regex:/[A-Z]/|regex:/[a-z]/|regex:/[0-9]/',
        ]);
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->nip = $data['nip'] ?? null;
        $user->role = $data['role'];
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();
        return redirect()->route('admin.users.index')->with('success','User diperbarui');
    }

    public function destroy(User $user)
    {
        // Larangan: tidak boleh hapus akun sendiri, admin lain, atau pimpinan
        $auth = auth()->user();
        if ($user->id === $auth->id) {
            return back()->withErrors(['Tidak dapat menghapus akun sendiri.']);
        }
        if (in_array($user->role, ['admin','pimpinan'])) {
            return back()->withErrors(['Tidak dapat menghapus akun dengan role: '.$user->role]);
        }
        $user->delete();
        return redirect()->route('admin.users.index')->with('success','User dihapus');
    }
}