<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('whatsapp_logs', function (Blueprint $table) {
            $table->id();
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->foreignId('penempatan_pkl_id')->nullable()->constrained('penempatan_pkl')->nullOnDelete();
            $table->foreignId('siswa_id')->nullable()->constrained('siswa')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recipient_phone', 30);
            $table->string('message_type', 50)->default('reminder_masuk');
            $table->text('message_content');
            $table->enum('status', ['pending', 'sending', 'sent', 'failed', 'skipped'])->default('pending');
            $table->text('error_reason')->nullable();
            $table->json('response_payload')->nullable();
            $table->date('tanggal');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['penempatan_pkl_id', 'tanggal', 'message_type'], 'idx_wa_penempatan_tgl_type');
            $table->index(['status', 'tanggal'], 'idx_wa_status_tanggal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_logs');
    }
};
