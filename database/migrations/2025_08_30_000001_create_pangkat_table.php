<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pangkat', function (Blueprint $table) {
            $table->id();
            $table->string('nama_pangkat');
            $table->string('golongan');
            $table->string('ruang');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pangkat');
    }
};
