<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table): void {
            $table->id();
            // Semua FK restrict: laporan masyarakat tidak boleh ikut terhapus
            // ketika akun, sekolah, atau distribusi yang dirujuk dihapus.
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('distribution_id')->constrained()->restrictOnDelete();
            $table->string('reporter_relation', 20);
            $table->date('incident_date');
            $table->unsignedSmallInteger('affected_count');
            $table->string('symptoms');
            $table->text('description');
            $table->string('status', 30)->default('dikirim')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
