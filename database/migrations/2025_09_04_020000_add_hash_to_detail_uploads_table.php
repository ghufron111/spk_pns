<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('detail_uploads', function (Blueprint $table) {
            if(!Schema::hasColumn('detail_uploads','hash')){
                $table->string('hash',64)->nullable()->after('path_berkas')->index();
            }
        });
    }
    public function down(): void
    {
        Schema::table('detail_uploads', function (Blueprint $table) {
            if(Schema::hasColumn('detail_uploads','hash')){
                $table->dropColumn('hash');
            }
        });
    }
};
