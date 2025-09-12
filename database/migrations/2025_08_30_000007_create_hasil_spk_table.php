<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hasil_spk', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('upload_id');
            $table->enum('hasil', ['disetujui', 'dipertimbangkan', 'ditolak']);
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->foreign('upload_id')->references('id')->on('uploads')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hasil_spk');
    }
};
