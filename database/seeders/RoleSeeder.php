<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::updateOrCreate(
            ['name' => Role::ADMIN],
            ['display_name' => 'Admin Pengelola Form']
        );

        Role::updateOrCreate(
            ['name' => Role::USER],
            ['display_name' => 'Petugas / Pengisi Form']
        );
    }
}
