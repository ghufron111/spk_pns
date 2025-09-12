<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('uploads', function (Blueprint $table) {
            if(!Schema::hasColumn('uploads','periode')) {
                $table->string('periode', 30)->nullable()->after('jenis');
                $table->index(['user_id','periode']);
            }
        });
        // Inisialisasi periode lama (isi tahun dari tanggal_upload) jika null
        DB::table('uploads')->whereNull('periode')->update([
            'periode' => DB::raw('YEAR(tanggal_upload)')
        ]);
    }
    public function down(): void
    {
        Schema::table('uploads', function (Blueprint $table) {
            if(Schema::hasColumn('uploads','periode')) {
                $table->dropIndex(['user_id','periode']);
                $table->dropColumn('periode');
            }
        });
    }
};
