<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table): void {
            // Bukti wajib: setiap laporan menyimpan satu file di disk private.
            $table->string('evidence_path')->after('description');
            // Nama asli hanya untuk ditampilkan, tidak dipakai sebagai nama file di server.
            $table->string('evidence_name')->after('evidence_path');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table): void {
            $table->dropColumn(['evidence_path', 'evidence_name']);
        });
    }
};
