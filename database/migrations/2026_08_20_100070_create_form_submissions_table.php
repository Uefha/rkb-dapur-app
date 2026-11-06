<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('forms')->restrictOnDelete();
            $table->foreignId('form_version_id')->constrained('form_versions')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->enum('status', ['draft', 'submitted', 'reviewed', 'approved', 'rejected'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Nama index custom & singkat -- nama otomatis Laravel untuk index
            // ini akan melebihi batas 64 karakter MySQL (pelajaran dari
            // Financial Performance Tracker).
            $table->index(['user_id', 'status'], 'fsub_user_status_idx');
            $table->index(['form_id', 'status'], 'fsub_form_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submissions');
    }
};
