<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('batas_waktu')) {
            // Sudah ada tabel (struktur terbaru akan ditangani oleh migrasi yang lebih baru)
            return;
        }
        Schema::create('batas_waktu', function (Blueprint $table) {
            $table->id();
            $table->dateTime('mulai');
            $table->dateTime('berakhir');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('batas_waktu')) {
            Schema::drop('batas_waktu');
        }
    }
};
