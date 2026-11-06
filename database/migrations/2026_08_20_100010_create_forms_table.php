<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('form_categories')->restrictOnDelete();
            $table->string('code')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('purpose')->nullable();       // Tujuan
            $table->text('instructions')->nullable();  // Petunjuk Pengisian

            // Status katalog (visibilitas ke User), BUKAN status struktur versi.
            // Divalidasi di level aplikasi (Form::STATUSES), bukan DB enum,
            // supaya tidak perlu ALTER TABLE saat kebutuhan status berkembang.
            $table->string('status')->default('draft'); // draft | published | archived

            // FK ke form_versions ditambahkan di migration terpisah karena
            // form_versions baru dibuat setelah tabel ini (dependensi melingkar).
            $table->unsignedBigInteger('current_version_id')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forms');
    }
};
