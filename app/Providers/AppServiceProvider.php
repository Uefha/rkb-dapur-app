<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Proyek ini memakai Bootstrap 5 di seluruh halaman (bukan Tailwind).
        // Tanpa baris ini, Paginator::links() akan merender markup pagination
        // bergaya Tailwind yang tidak akan tampil sesuai desain sama sekali
        // karena tidak ada CSS Tailwind yang dimuat di proyek ini.
        Paginator::useBootstrapFive();
    }
}
