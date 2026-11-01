<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jejak perubahan status laporan yang diisi ReportObserver. Sengaja hanya
     * menyimpan ID dan status, tanpa isi laporan atau data pribadi pelapor.
     */
    public function up(): void
    {
        Schema::create('report_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            // Null bila perubahan tidak dilakukan pengguna yang login (mis. seeder/console).
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // Null saat laporan baru dibuat.
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_status_logs');
    }
};
