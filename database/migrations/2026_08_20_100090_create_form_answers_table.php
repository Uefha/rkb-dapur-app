<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_submission_id')->constrained('form_submissions')->cascadeOnDelete();
            $table->foreignId('form_field_id')->constrained('form_fields')->restrictOnDelete();
            $table->foreignId('form_submission_row_id')->nullable()->constrained('form_submission_rows')->cascadeOnDelete();
            // Hanya diisi kalau form_submission_row_id ada (jawaban sel di
            // dalam Dynamic Table) -- menyimpan KOLOM mana jawaban ini milik
            // (mis. "nama_bahan", "jumlah"). Tanpa ini, satu baris tabel yang
            // punya beberapa sel tidak bisa dibedakan kolomnya lagi begitu
            // dibaca ulang untuk ditampilkan atau untuk melanjutkan draft.
            $table->string('column_key')->nullable();
            $table->longText('value')->nullable();
            $table->timestamps();

            $table->index(['form_submission_id', 'form_field_id'], 'fans_submission_field_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_answers');
    }
};
