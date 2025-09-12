<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
	public function up(): void
	{
		if (!Schema::hasTable('uploads')) {
			Schema::create('uploads', function (Blueprint $table) {
				$table->id();
				$table->foreignId('user_id')->constrained('users')->onDelete('cascade');
				$table->date('tanggal_upload');
				$table->string('status')->default('pending');
				$table->timestamps();
			});
		}
	}

	public function down(): void
	{
		Schema::dropIfExists('uploads');
	}
};
