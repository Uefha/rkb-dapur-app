<?php

namespace Database\Seeders;

use App\Models\FormCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FormCategorySeeder extends Seeder
{
    /**
     * 7 kategori awal sesuai bagian 14 spesifikasi. Admin bisa menambah
     * kategori baru sendiri lewat menu Kategori Form -- daftar ini hanya
     * titik awal, bukan daftar tertutup.
     */
    public function run(): void
    {
        $categories = [
            'Logistik' => 'Penerimaan, pengadaan, dan distribusi bahan makanan.',
            'Penyimpanan' => 'Pemantauan suhu dan kondisi penyimpanan bahan makanan.',
            'Produksi' => 'Pencatatan kegiatan produksi dan jumlah porsi makanan.',
            'Keamanan Pangan' => 'Higiene personalia, APD, dan kepatuhan keamanan pangan.',
            'Operasional' => 'Laporan dan checklist operasional harian dapur.',
            'SDM' => 'Jadwal kerja, shift, dan kehadiran petugas/relawan dapur.',
            'Serah Terima' => 'Serah terima tanggung jawab dan kondisi fasilitas dapur.',
        ];

        foreach ($categories as $name => $description) {
            // Slug dibuat langsung dari Str::slug (deterministik), BUKAN lewat
            // FormCategory::generateUniqueSlug() -- method itu sengaja menghindari
            // slug yang sudah ada, sehingga kalau dipakai di sini justru
            // menggagalkan idempotensi: jalan ulang akan bikin baris duplikat
            // ("logistik-2") alih-alih meng-update baris "logistik" yang sudah ada.
            FormCategory::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'description' => $description]
            );
        }
    }
}
