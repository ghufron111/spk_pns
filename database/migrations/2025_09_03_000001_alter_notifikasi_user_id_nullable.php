<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Lepas foreign key jika ada, tanpa Doctrine.
        try {
            Schema::table('notifikasi', function (Blueprint $table) {
                // Nama default biasanya notifikasi_user_id_foreign
                $table->dropForeign(['user_id']);
            });
        } catch (\Throwable $e) {
            // abaikan jika tidak ada
        }
        Schema::table('notifikasi', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        try {
            Schema::table('notifikasi', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        } catch (\Throwable $e) {}
        Schema::table('notifikasi', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
