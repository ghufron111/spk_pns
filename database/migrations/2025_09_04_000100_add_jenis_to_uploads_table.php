<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('uploads', function (Blueprint $table) {
            if(!Schema::hasColumn('uploads','jenis')){
                $table->string('jenis')->nullable()->after('user_id'); // reguler|pilihan|ijazah
            }
        });
    }
    public function down(): void
    {
        Schema::table('uploads', function (Blueprint $table) {
            if(Schema::hasColumn('uploads','jenis')){
                $table->dropColumn('jenis');
            }
        });
    }
};
