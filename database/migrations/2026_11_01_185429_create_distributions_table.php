<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distributions', function (Blueprint $table): void {
            $table->id();
            // Restrict: sekolah yang masih punya distribusi tidak boleh terhapus,
            // agar tidak ada distribusi (dan nanti laporan) tanpa sekolah.
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->date('distribution_date');
            $table->string('menu');
            // SPPG disimpan sebagai teks: pihak eksternal, bukan pengguna aplikasi.
            $table->string('provider_name', 150);
            $table->unsignedInteger('portion_count')->nullable();
            $table->timestamps();

            // Satu sekolah hanya menerima satu distribusi per tanggal.
            $table->unique(['school_id', 'distribution_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distributions');
    }
};
