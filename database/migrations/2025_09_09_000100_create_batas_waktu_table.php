<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('batas_waktu', function (Blueprint $table) {
            $table->id();
            $table->string('label', 50);
            $table->date('mulai');
            $table->date('selesai');
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
    Schema::dropIfExists('batas_waktu');
    }
};
