<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Membuat 1 akun Admin default untuk login pertama kali.
     * PENTING: ganti password ini segera setelah login pertama di lingkungan produksi.
     */
    public function run(): void
    {
        $adminRole = Role::where('name', Role::ADMIN)->firstOrFail();

        User::updateOrCreate(
            ['email' => 'admin@rkb-dapur.test'],
            [
                'role_id' => $adminRole->id,
                'name' => 'Administrator RKB Dapur',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
    }
}
