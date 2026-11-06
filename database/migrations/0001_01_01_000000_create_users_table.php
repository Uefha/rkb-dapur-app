<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nama file migration ini SENGAJA dibuat identik dengan file bawaan
 * `laravel new` (0001_01_01_000000_create_users_table.php). Tujuannya supaya
 * saat file project ini ditimpakan ke skeleton Laravel kosong, proses
 * timpa/replace file benar-benar MENGGANTIKAN migration bawaan, bukan
 * menambah file baru di sampingnya.
 *
 * Sebelumnya `roles` dan `users` berada di dua file terpisah
 * (0001_01_01_000000_create_roles_table.php +
 *  0001_01_01_000001_create_users_table.php). Karena nama file itu tidak
 * sama dengan bawaan Laravel, kalau proses timpa file tidak menghapus dulu
 * migration bawaan, hasilnya ada DUA migration yang sama-sama membuat tabel
 * `users` -- migration kedua yang jalan akan gagal dengan error
 * "Table 'users' already exists". Digabung jadi satu file dengan nama
 * bawaan supaya kasus ini tidak bisa terulang lagi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // admin | user
            $table->string('display_name')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->restrictOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');
    }
};
