<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ClearRandomDemoSubmissionsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $marker = AppSetting::where('key', 'random_demo_submissions_100_created')->lockForUpdate()->first();
            if (! $marker) {
                $this->command?->warn('Penanda batch tidak ditemukan; tidak ada submission yang dihapus.');
                return;
            }

            $user = User::where('email', 'petugas@rkb-dapur.test')->first();
            if (! $user) {
                throw new RuntimeException('Akun petugas contoh tidak ditemukan; tidak ada submission yang dihapus.');
            }

            $ids = FormSubmission::where('user_id', $user->id)->orderByDesc('id')->limit(100)->pluck('id');
            if ($ids->count() !== 100) {
                throw new RuntimeException('Tidak ditemukan 100 submission untuk batch dummy; penghapusan dibatalkan.');
            }

            FormSubmission::whereIn('id', $ids)->forceDelete();
            $marker->delete();
            $this->command?->info('100 submission dummy beserta jawabannya berhasil dihapus.');
        });
    }
}
