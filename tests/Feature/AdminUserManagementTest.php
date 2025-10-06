<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function(){
    // Buat admin login
    $this->admin = User::factory()->create(['role'=>'admin','password'=>Hash::make('AdminP4ss')]);
    $this->actingAs($this->admin);
});

it('can create a new user', function(){
    $resp = $this->post(route('admin.users.store'), [
        'name' => 'User Baru',
        'email' => 'baru@example.test',
        'role' => 'pegawai',
        'password' => 'Password1',
        'password_confirmation' => 'Password1'
    ]);
    $resp->assertRedirect(route('admin.users.index'));
    $this->assertDatabaseHas('users', [
        'email' => 'baru@example.test',
        'role' => 'pegawai'
    ]);
});

it('can update user without changing password', function(){
    $user = User::factory()->create(['role'=>'pegawai']);
    $oldHash = $user->password;
    $resp = $this->put(route('admin.users.update',$user), [
        'name' => 'Nama Update',
        'email' => $user->email,
        'role' => 'pimpinan',
        'password' => '',
        'password_confirmation' => ''
    ]);
    $resp->assertRedirect(route('admin.users.index'));
    $user->refresh();
    expect($user->name)->toBe('Nama Update');
    expect($user->role)->toBe('pimpinan');
    expect($user->password)->toBe($oldHash);
});

it('prevent deleting itself', function(){
    $resp = $this->delete(route('admin.users.destroy',$this->admin));
    $resp->assertSessionHasErrors();
    $this->assertDatabaseHas('users',[ 'id'=>$this->admin->id ]);
});

it('prevent deleting another admin', function(){
    $otherAdmin = User::factory()->create(['role'=>'admin']);
    $resp = $this->delete(route('admin.users.destroy',$otherAdmin));
    $resp->assertSessionHasErrors();
    $this->assertDatabaseHas('users',[ 'id'=>$otherAdmin->id ]);
});

it('prevent deleting pimpinan', function(){
    $pimpinan = User::factory()->create(['role'=>'pimpinan']);
    $resp = $this->delete(route('admin.users.destroy',$pimpinan));
    $resp->assertSessionHasErrors();
    $this->assertDatabaseHas('users',[ 'id'=>$pimpinan->id ]);
});

it('orders users by id desc', function(){
    $u1 = User::factory()->create(); // first
    $u2 = User::factory()->create(); // second
    $u3 = User::factory()->create(); // third (highest id)
    $resp = $this->get(route('admin.users.index'));
    $resp->assertStatus(200);
    $html = $resp->getContent();
    // Ambil semua kemunculan kolom ID pertama setiap baris (kolom 1)
    preg_match_all('/<td>(\d+)<\/td>\s*<td>/', $html, $matches);
    $ids = $matches[1] ?? [];
    // Pastikan urutan menampilkan id terbesar lebih dulu
    if (count($ids) >= 3) {
        expect((int)$ids[0])->toBe($u3->id);
    }
});
