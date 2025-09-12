<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('notifikasi', function (Blueprint $table) {
            // Ubah kolom pesan menjadi text jika saat ini string/varchar
            // MySQL: perlu modify. Laravel: gunakan ->text() setelah drop/modify.
            // Cara aman: tambahkan kolom baru sementara lalu salin data? Simpel: modify langsung.
            $table->text('pesan')->change();
        });
    }

    public function down(): void
    {
        Schema::table('notifikasi', function (Blueprint $table) {
            $table->string('pesan',255)->change();
        });
    }
};