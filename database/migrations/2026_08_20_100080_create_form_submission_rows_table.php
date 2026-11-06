<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_submission_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_submission_id')->constrained('form_submissions')->cascadeOnDelete();
            $table->foreignId('form_field_id')->constrained('form_fields')->restrictOnDelete();
            $table->unsignedInteger('row_index');
            $table->timestamps();

            $table->unique(['form_submission_id', 'form_field_id', 'row_index'], 'fsrow_unique_row_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submission_rows');
    }
};
