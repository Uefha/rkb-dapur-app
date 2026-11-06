<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;

class RecoverAdminAccess extends Command
{
    protected $signature = 'admin:recover';

    protected $description = 'Pulihkan akses akun admin melalui terminal server';

    public function handle(): int
    {
        $admins = User::query()
            ->whereHas('role', fn ($query) => $query->where('name', Role::ADMIN))
            ->orderBy('email')
            ->get();

        if ($admins->isEmpty()) {
            $this->error('Tidak ada akun admin yang ditemukan.');
            return self::FAILURE;
        }

        $this->table(['Nama admin', 'Email login'], $admins->map(fn (User $admin) => [
            $admin->name,
            $admin->email,
        ])->all());

        $selectedEmail = $this->choice('Pilih email admin yang akan dipulihkan', $admins->pluck('email')->all());
        $admin = $admins->firstWhere('email', $selectedEmail);

        if (! $admin) {
            $this->error('Pilihan akun admin tidak valid.');
            return self::FAILURE;
        }

        $password = $this->secret('Kata sandi baru (minimal 12 karakter)');
        $confirmation = $this->secret('Ulangi kata sandi baru');

        if (! is_string($password) || strlen($password) < 12) {
            $this->error('Kata sandi harus terdiri dari minimal 12 karakter.');
            return self::FAILURE;
        }

        if (! is_string($confirmation) || ! hash_equals($password, $confirmation)) {
            $this->error('Konfirmasi kata sandi tidak cocok. Tidak ada perubahan yang disimpan.');
            return self::FAILURE;
        }

        $admin->forceFill([
            'password' => $password,
            'is_active' => true,
            'email_verified_at' => $admin->email_verified_at ?? now(),
        ])->save();

        $this->info("Akses dipulihkan untuk {$admin->email}. Silakan login dengan kata sandi baru.");

        return self::SUCCESS;
    }
}
