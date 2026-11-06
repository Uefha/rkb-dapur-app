<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    /**
     * Akun contoh untuk role User (pengisi form), memudahkan testing
     * alur "Form Tersedia -> Isi -> Submit" tanpa perlu registrasi manual.
     */
    public function run(): void
    {
        $userRole = Role::where('name', Role::USER)->firstOrFail();

        User::updateOrCreate(
            ['email' => 'petugas@rkb-dapur.test'],
            [
                'role_id' => $userRole->id,
                'name' => 'Petugas Dapur (Contoh)',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
    }
}
