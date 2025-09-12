<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('users','id_user')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['id_user']);
                $table->dropColumn('id_user');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('id_user',32)->nullable()->unique();
        });
    }
};
