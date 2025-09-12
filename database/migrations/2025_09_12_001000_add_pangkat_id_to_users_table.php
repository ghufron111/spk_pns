<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users','pangkat_id')) {
                $table->unsignedBigInteger('pangkat_id')->nullable()->after('pangkat');
                $table->foreign('pangkat_id')->references('id')->on('pangkat')->nullOnDelete();
            }
        });
        // Backfill: attempt match by concatenating golongan+ruang or nama_pangkat containing users.pangkat
        $users = DB::table('users')->select('id','pangkat')->whereNotNull('pangkat')->get();
        $pangkatRows = DB::table('pangkat')->get();
        foreach ($users as $u) {
            $matchId = null;
            foreach ($pangkatRows as $p) {
                // Simple matching heuristics
                $candidate = trim($p->nama_pangkat);
                if (strcasecmp($candidate, $u->pangkat) === 0) { $matchId = $p->id; break; }
                if (stripos($candidate, $u->pangkat) !== false) { $matchId = $p->id; break; }
            }
            if ($matchId) {
                DB::table('users')->where('id',$u->id)->update(['pangkat_id'=>$matchId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users','pangkat_id')) {
                $table->dropForeign(['pangkat_id']);
                $table->dropColumn('pangkat_id');
            }
        });
    }
};
