<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('uploads','target_pangkat')) {
            Schema::table('uploads', function (Blueprint $table) {
                $table->string('target_pangkat',20)->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('uploads','target_pangkat')) {
            Schema::table('uploads', function (Blueprint $table) {
                $table->dropColumn('target_pangkat');
            });
        }
    }
};
