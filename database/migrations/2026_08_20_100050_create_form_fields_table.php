<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_section_id')->constrained('form_sections')->cascadeOnDelete();

            // Field induk untuk field anak (dipakai field tipe Repeater, menyusul
            // di fase lanjutan). Belum dipakai UI Builder dasar, kolom disiapkan
            // dari awal supaya tidak perlu ALTER TABLE saat Repeater dibangun.
            $table->foreignId('parent_field_id')->nullable()->constrained('form_fields')->cascadeOnDelete();

            // Divalidasi via whitelist di app\Models\FormField::TYPES (bukan DB enum)
            // supaya penambahan tipe field baru (Phase 3, 8) tidak perlu ALTER TABLE.
            $table->string('field_type');

            $table->string('name');       // key mesin, auto-slug dari label
            $table->text('label');        // teks label / isi heading & static text
            $table->string('placeholder')->nullable();
            $table->text('help_text')->nullable();
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('order')->default(0);

            // Disiapkan untuk konfigurasi lanjutan per tipe field (kolom Dynamic
            // Table di Phase 3, formula Calculated Field di Phase 8, dst.)
            $table->json('config')->nullable();

            $table->timestamps();

            $table->unique(['form_section_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_fields');
    }
};
