<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forwardings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            // Koordinator yang mencatat penerusan.
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            // Pihak penerima bukan pengguna aplikasi, cukup dicatat sebagai teks.
            $table->string('recipient_type', 20);
            $table->string('recipient_name', 150);
            $table->date('forwarded_at');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forwardings');
    }
};
