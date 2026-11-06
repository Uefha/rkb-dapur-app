<?php

namespace Database\Seeders;

use App\Models\Form;
use App\Models\FormCategory;
use App\Models\Role;
use App\Models\User;
use App\Services\DynamicFormService;
use App\Services\FormBuilderService;
use Illuminate\Database\Seeder;

/**
 * 9 form awal dari "Kumpulan 9 Form PIC RKB Dapur.pdf", dibangun lewat
 * DynamicFormService & FormBuilderService -- jalur kode yang SAMA dengan
 * yang dipakai Admin lewat UI Form Builder. Ini bukan tabel/struktur khusus:
 * data ini murni contoh pertama yang mengisi mesin dinamis yang sudah ada.
 *
 * Idempotent: form yang kode dokumennya sudah ada (match persis, termasuk
 * yang sudah di-soft-delete) dilewati, aman dijalankan ulang lewat db:seed.
 */
class FormSeeder extends Seeder
{
    public function __construct(
        private readonly DynamicFormService $formService,
        private readonly FormBuilderService $builder,
    ) {
    }

    public function run(): void
    {
        $admin = User::whereHas('role', fn ($q) => $q->where('name', Role::ADMIN))->firstOrFail();

        foreach ($this->formDefinitions() as $definition) {
            // withTrashed(): kode dokumen form yang sudah di-soft-delete tetap
            // diobfuscate (lihat FormController::destroy), jadi tidak akan pernah
            // match di sini lagi -- cek ini murni untuk mencegah duplikat pada
            // form yang masih aktif saat db:seed dijalankan ulang.
            if (Form::withTrashed()->where('code', $definition['code'])->exists()) {
                continue;
            }

            $category = FormCategory::where('name', $definition['category'])->firstOrFail();

            $form = $this->formService->createForm([
                'category_id' => $category->id,
                'code' => $definition['code'],
                'title' => $definition['title'],
                'description' => $definition['description'] ?? null,
                'purpose' => $definition['purpose'] ?? null,
                'instructions' => $definition['instructions'] ?? null,
            ], $admin);

            $version = $form->latestVersion();

            foreach ($definition['sections'] as $sectionDef) {
                $section = $this->builder->addSection($version, [
                    'title' => $sectionDef['title'],
                    'description' => $sectionDef['description'] ?? null,
                ]);

                foreach ($sectionDef['fields'] as $fieldDef) {
                    $this->builder->addField($section, $this->toFieldPayload($fieldDef));
                }
            }

            $this->formService->publishForm($form);
        }
    }

    /**
     * Terjemahkan bentuk ringkas dari formDefinitions() ke bentuk array yang
     * diharapkan FormBuilderService::addField() (field_type, is_required, dst.),
     * persis seperti payload yang dikirim form HTTP dari Builder UI.
     */
    private function toFieldPayload(array $fieldDef): array
    {
        $payload = [
            'field_type' => $fieldDef['type'],
            'label' => $fieldDef['label'],
            'is_required' => $fieldDef['required'] ?? false,
            'help_text' => $fieldDef['help_text'] ?? null,
        ];

        if (in_array($fieldDef['type'], ['select', 'radio', 'checkbox'], true)) {
            $payload['options'] = array_map(
                fn (string $label) => ['label' => $label, 'value' => null],
                $fieldDef['options'] ?? []
            );
        }

        if ($fieldDef['type'] === 'dynamic_table') {
            $payload['columns'] = $fieldDef['columns'];
            $payload['default_rows'] = $fieldDef['default_rows'] ?? [];
        }

        return $payload;
    }

    // ==================================================================
    // Helper pembentuk definisi field ringkas -- dipakai di formDefinitions()
    // supaya struktur 9 form di bawah tetap terbaca, bukan array mentah.
    // ==================================================================

    private function text(string $label, bool $required = false, ?string $help = null): array
    {
        return ['type' => 'text', 'label' => $label, 'required' => $required, 'help_text' => $help];
    }

    private function textarea(string $label, bool $required = false): array
    {
        return ['type' => 'textarea', 'label' => $label, 'required' => $required];
    }

    private function number(string $label, bool $required = false): array
    {
        return ['type' => 'number', 'label' => $label, 'required' => $required];
    }

    private function date(string $label, bool $required = false): array
    {
        return ['type' => 'date', 'label' => $label, 'required' => $required];
    }

    private function select(string $label, array $options, bool $required = false): array
    {
        return ['type' => 'select', 'label' => $label, 'options' => $options, 'required' => $required];
    }

    private function checkbox(string $label, array $options, bool $required = false): array
    {
        return ['type' => 'checkbox', 'label' => $label, 'options' => $options, 'required' => $required];
    }

    /**
     * @param  array<int, array>  $columns  hasil dari $this->col(...)
     * @param  array<int, array<string, string>>  $defaultRows  baris checklist tetap (opsional)
     */
    private function table(string $label, array $columns, array $defaultRows = [], bool $required = false): array
    {
        return [
            'type' => 'dynamic_table',
            'label' => $label,
            'required' => $required,
            'columns' => $columns,
            'default_rows' => $defaultRows,
        ];
    }

    /**
     * Definisi satu kolom Dynamic Table. $key eksplisit dipakai (bukan
     * mengandalkan auto-slug dari label) supaya key ini bisa dipakai dengan
     * pasti sebagai referensi di $defaultRows tanpa risiko meleset akibat
     * transliterasi karakter khusus (°, /, dst.) di dalam label.
     */
    private function col(string $label, string $type = 'text', array $options = [], ?string $key = null): array
    {
        $column = ['label' => $label, 'type' => $type];

        if ($key !== null) {
            $column['key'] = $key;
        }

        if ($type === 'select') {
            $column['options'] = $options;
        }

        return $column;
    }

    /**
     * Pola "Verifikasi & Tanda Tangan" yang berulang di hampir semua form:
     * baris tetap per jabatan, kolom Nama/Tanda Tangan/Tanggal diisi User.
     * Kolom Tanda Tangan masih bertipe text di Phase 3 (tipe field
     * "signature" baru aktif di Phase 8) -- dicatat di README sebagai
     * keterbatasan yang diketahui, bukan diam-diam disembunyikan.
     */
    private function verificationTable(string $label, array $jabatanList): array
    {
        return $this->table(
            $label,
            [
                $this->col('Jabatan', 'text', [], 'jabatan'),
                $this->col('Nama'),
                $this->col('Tanda Tangan'),
                $this->col('Tanggal', 'date'),
            ],
            array_map(fn (string $jabatan) => ['jabatan' => $jabatan], $jabatanList)
        );
    }

    private function formDefinitions(): array
    {
        return [
            $this->form01(),
            $this->form02(),
            $this->form03(),
            $this->form04(),
            $this->form05(),
            $this->form06(),
            $this->form07(),
            $this->form08(),
            $this->form09(),
        ];
    }

    private function form01(): array
    {
        return [
            'category' => 'Logistik',
            'code' => 'FRM-DU/01',
            'title' => 'Formulir Penerimaan Bahan Makanan',
            'description' => 'Mencatat dan mengontrol bahan makanan yang diterima dapur umum dari pemasok, donatur, atau gudang internal.',
            'purpose' => 'Untuk mencatat dan mengontrol setiap bahan makanan yang diterima oleh dapur umum, baik dari pemasok, donatur, maupun gudang internal, sehingga dapat dipastikan kesesuaian jumlah, kualitas, dan kelayakannya sebelum digunakan dalam proses pengolahan.',
            'instructions' => "1. Formulir diisi oleh petugas penerima bahan makanan setiap kali ada pengiriman masuk.\n2. Pastikan pemeriksaan kualitas dan kesesuaian data dilakukan bersama pemasok atau perwakilan logistik.\n3. Beri tanda centang (✓) pada kolom sesuai jika kondisi bahan baik, dan tulis catatan pada kolom keterangan jika ada ketidaksesuaian.\n4. Formulir ini disimpan sebagai arsip operasional harian.",
            'sections' => [
                [
                    'title' => 'Informasi Umum',
                    'fields' => [
                        $this->date('Tanggal Penerimaan', true),
                        $this->text('Nomor Formulir', true),
                        $this->text('Nama Pemasok / Donatur', true),
                        $this->text('Alamat / Kontak'),
                        $this->text('Nomor Pesanan / Surat Jalan'),
                        $this->text('Diterima Oleh', true),
                        $this->text('Disaksikan Oleh'),
                    ],
                ],
                [
                    'title' => 'Rincian Bahan yang Diterima',
                    'fields' => [
                        $this->table('Rincian Bahan yang Diterima', [
                            $this->col('Nama Bahan Makanan'),
                            $this->col('Jenis', 'select', ['Basah', 'Kering', 'Beku']),
                            $this->col('Jumlah Diterima', 'number'),
                            $this->col('Satuan'),
                            $this->col('Kondisi Fisik'),
                            $this->col('Tanggal Kadaluarsa / Panen', 'date'),
                            $this->col('Sesuai / Tidak Sesuai', 'select', ['Sesuai', 'Tidak Sesuai']),
                            $this->col('Keterangan / Catatan'),
                        ], required: true),
                    ],
                ],
                [
                    'title' => 'Pemeriksaan Kualitas Bahan',
                    'fields' => [
                        $this->table('Pemeriksaan Kualitas Bahan', [
                            $this->col('Aspek Pemeriksaan', 'text', [], 'aspek'),
                            $this->col('Kriteria', 'text', [], 'kriteria'),
                            $this->col('Hasil Pemeriksaan', 'select', ['Sesuai', 'Tidak Sesuai']),
                            $this->col('Catatan'),
                        ], [
                            ['aspek' => 'Warna & aroma bahan', 'kriteria' => 'Sesuai standar bahan segar'],
                            ['aspek' => 'Tekstur & kondisi fisik', 'kriteria' => 'Tidak busuk, berlendir, atau rusak'],
                            ['aspek' => 'Kemasan bahan kering', 'kriteria' => 'Tidak sobek, bocor, atau lembab'],
                            ['aspek' => 'Suhu bahan beku saat diterima', 'kriteria' => '≤ -18°C (sesuai standar)'],
                            ['aspek' => 'Label & tanggal kadaluarsa', 'kriteria' => 'Terbaca jelas & belum lewat batas'],
                        ]),
                    ],
                ],
                [
                    'title' => 'Rekapitulasi',
                    'fields' => [
                        $this->table('Rekapitulasi', [
                            $this->col('Kategori', 'text', [], 'kategori'),
                            $this->col('Jumlah Item', 'number'),
                            $this->col('Diterima', 'number'),
                            $this->col('Ditolak', 'number'),
                            $this->col('Keterangan'),
                        ], [
                            ['kategori' => 'Bahan Basah'],
                            ['kategori' => 'Bahan Kering'],
                            ['kategori' => 'Bahan Beku'],
                            ['kategori' => 'Total'],
                        ]),
                    ],
                ],
                [
                    'title' => 'Verifikasi & Tanda Tangan',
                    'fields' => [
                        $this->verificationTable('Verifikasi & Tanda Tangan', [
                            'Petugas Penerima', 'Koordinator Dapur', 'Pemasok / Donatur',
                        ]),
                    ],
                ],
            ],
        ];
    }

    private function form02(): array
    {
        return [
            'category' => 'Penyimpanan',
            'code' => 'CHK-DU/02',
            'title' => 'Checklist Suhu Penyimpanan (Kulkas / Freezer)',
            'description' => 'Pemantauan rutin suhu kulkas dan freezer penyimpanan bahan makanan, dua kali sehari.',
            'purpose' => 'Memastikan bahwa suhu penyimpanan pada kulkas dan freezer selalu berada dalam batas aman yang direkomendasikan untuk mencegah pertumbuhan mikroba dan menjaga kualitas bahan makanan.',
            'instructions' => "1. Catat suhu penyimpanan dua kali sehari (pagi dan sore) pada setiap unit kulkas atau freezer.\n2. Batas suhu ideal -- Kulkas (Chiller): 0°C – 5°C, Freezer: -18°C atau lebih rendah.\n3. Jika suhu tidak sesuai standar, segera lakukan tindakan korektif dan laporkan ke Koordinator Dapur.\n4. Simpan formulir checklist ini sebagai bukti pemantauan suhu harian.",
            'sections' => [
                [
                    'title' => 'Formulir Pemantauan Suhu',
                    'fields' => [
                        $this->table('Formulir Pemantauan Suhu', [
                            $this->col('Tanggal', 'date'),
                            $this->col('Nama Unit (Kulkas/Freezer)', 'select', ['Kulkas', 'Freezer']),
                            $this->col('Jenis Bahan yang Disimpan'),
                            $this->col('Waktu Pengecekan', 'select', ['Pagi', 'Siang', 'Malam']),
                            $this->col('Suhu Tercatat (°C)', 'number'),
                            $this->col('Suhu Ideal (°C)'),
                            $this->col('Sesuai', 'select', ['Sesuai', 'Tidak Sesuai']),
                            $this->col('Tindakan Korektif (Jika Tidak Sesuai)'),
                            $this->col('Paraf Petugas'),
                        ], required: true),
                    ],
                ],
                [
                    'title' => 'Rangkuman Harian',
                    'fields' => [
                        $this->number('Total Unit Kulkas/Freezer Diperiksa'),
                        $this->number('Jumlah Unit dengan Suhu Sesuai Standar'),
                        $this->number('Jumlah Unit dengan Suhu Tidak Sesuai'),
                        $this->text('Tindakan Korektif Dilakukan Oleh'),
                        $this->textarea('Catatan Tambahan'),
                    ],
                ],
                [
                    'title' => 'Verifikasi & Tanda Tangan',
                    'fields' => [
                        $this->verificationTable('Verifikasi & Tanda Tangan', ['Petugas Pemeriksa', 'Koordinator Dapur']),
                    ],
                ],
            ],
        ];
    }

    private function form03(): array
    {
        return [
            'category' => 'Produksi',
            'code' => 'FRM-DU/02',
            'title' => 'Formulir Catatan Produksi & Jumlah Porsi',
            'description' => 'Mencatat kegiatan produksi harian: menu, bahan yang digunakan, dan hasil porsi.',
            'purpose' => 'Untuk mencatat data kegiatan produksi harian, termasuk jenis menu, jumlah bahan yang digunakan, hasil porsi yang dihasilkan, dan keterangan efisiensi. Data ini digunakan sebagai dasar perencanaan menu berikutnya, evaluasi stok bahan, serta pengendalian mutu dan gizi makanan yang disajikan.',
            'instructions' => "1. Diisi setiap kali kegiatan produksi dilakukan (pagi, siang, malam).\n2. Catat semua bahan utama dan tambahan yang digunakan untuk setiap jenis menu.\n3. Jumlah porsi yang dihasilkan harus sesuai dengan standar takaran saji yang telah ditetapkan.\n4. Setelah produksi selesai, form diserahkan ke Koordinator Dapur untuk verifikasi dan pelaporan.",
            'sections' => [
                [
                    'title' => 'Data Umum Produksi',
                    'fields' => [
                        $this->date('Tanggal Produksi', true),
                        $this->select('Shift / Waktu Produksi', ['Pagi', 'Siang', 'Malam'], true),
                        $this->text('Nama Koki Utama / Tim'),
                        $this->text('Menu Utama', true),
                        $this->text('Menu Pendamping / Lauk'),
                        $this->text('Menu Sayur / Buah'),
                        $this->text('Menu Minuman'),
                    ],
                ],
                [
                    'title' => 'Rincian Penggunaan Bahan & Hasil Produksi',
                    'fields' => [
                        $this->table('Rincian Penggunaan Bahan & Hasil Produksi', [
                            $this->col('Nama Bahan Makanan'),
                            $this->col('Jenis', 'select', ['Basah', 'Kering', 'Beku']),
                            $this->col('Jumlah Digunakan', 'number'),
                            $this->col('Satuan'),
                            $this->col('Jumlah Porsi Dihasilkan', 'number'),
                            $this->col('Standar Porsi per Orang', 'number'),
                            $this->col('Selisih (±)', 'number'),
                            $this->col('Keterangan'),
                        ], required: true),
                    ],
                ],
                [
                    'title' => 'Rekapitulasi Produksi',
                    'fields' => [
                        $this->number('Total Bahan Digunakan'),
                        $this->number('Total Porsi Dihasilkan'),
                        $this->number('Jumlah Porsi Sesuai Target'),
                        $this->number('Jumlah Porsi Berlebih'),
                        $this->number('Jumlah Porsi Kurang'),
                    ],
                ],
                [
                    'title' => 'Pemeriksaan Kualitas & Cita Rasa',
                    'fields' => [
                        // Catatan: pilihan hasil pemeriksaan per aspek berbeda-beda di PDF
                        // sumber (mis. "Baik/Kurang/Berlebih" utk kematangan vs "Sesuai
                        // resep/Perlu penyesuaian" utk rasa). Dynamic Table Phase 3 memakai
                        // satu set opsi per KOLOM (bukan per baris), jadi seluruh pilihan
                        // digabung jadi satu daftar opsi bersama pada kolom ini -- sedikit
                        // longgar dibanding PDF, tapi tetap terstruktur (bukan free text).
                        // Opsi per-baris yang berbeda bisa ditambahkan di fase lanjutan jika
                        // dibutuhkan.
                        $this->table('Pemeriksaan Kualitas & Cita Rasa', [
                            $this->col('Aspek yang Diperiksa', 'text', [], 'aspek'),
                            $this->col('Hasil Pemeriksaan', 'select', [
                                'Baik', 'Kurang', 'Berlebih', 'Sesuai resep', 'Perlu penyesuaian',
                                'Layak saji', 'Tidak layak', 'Sesuai standar (>60°C)',
                            ]),
                            $this->col('Catatan'),
                        ], [
                            ['aspek' => 'Kematangan makanan'],
                            ['aspek' => 'Rasa & aroma'],
                            ['aspek' => 'Tekstur & penampilan'],
                            ['aspek' => 'Suhu saat disajikan'],
                        ]),
                    ],
                ],
                [
                    'title' => 'Distribusi & Sisa Makanan',
                    'fields' => [
                        $this->table('Distribusi & Sisa Makanan', [
                            $this->col('Uraian', 'text', [], 'uraian'),
                            $this->col('Jumlah', 'number'),
                            $this->col('Keterangan'),
                        ], [
                            ['uraian' => 'Jumlah porsi didistribusikan'],
                            ['uraian' => 'Jumlah sisa makanan layak konsumsi'],
                            ['uraian' => 'Jumlah sisa makanan tidak layak konsumsi'],
                        ]),
                        $this->select('Cara Penanganan Sisa Makanan', ['Didonasikan', 'Dibuang', 'Didaur ulang']),
                    ],
                ],
                [
                    'title' => 'Verifikasi & Tanda Tangan',
                    'fields' => [
                        $this->verificationTable('Verifikasi & Tanda Tangan', [
                            'Petugas Produksi', 'Koordinator Dapur', 'Petugas Distribusi',
                        ]),
                    ],
                ],
            ],
        ];
    }

    private function form04(): array
    {
        return [
            'category' => 'Keamanan Pangan',
            'code' => 'FRM-DU/04',
            'title' => 'Formulir Higiene Personalia (Cuci Tangan, APD, Kesehatan)',
            'description' => 'Memonitor kepatuhan petugas dapur terhadap kebersihan diri, prosedur cuci tangan, penggunaan APD, dan kondisi kesehatan harian.',
            'purpose' => 'Formulir ini digunakan untuk memonitor kepatuhan petugas dapur terhadap prosedur higiene personalia, seperti kebersihan tangan, penggunaan APD, serta kondisi kesehatan harian, demi menjaga keamanan pangan dan mencegah kontaminasi selama kegiatan produksi dan distribusi makanan.',
            'instructions' => "1. Diisi setiap awal dan akhir shift kerja oleh petugas K3 atau koordinator area.\n2. Centang (✓) jika sesuai standar, tanda silang (✗) jika tidak sesuai.\n3. Jika ditemukan pelanggaran atau kondisi tidak sehat, segera laporkan dan tindak lanjuti.\n4. Simpan formulir ini sebagai arsip harian keamanan pangan.",
            'sections' => [
                [
                    'title' => 'Data Umum',
                    'fields' => [
                        $this->date('Tanggal Pemeriksaan', true),
                        $this->select('Shift', ['Pagi', 'Siang', 'Malam'], true),
                        $this->text('Nama Pemeriksa', true),
                        $this->text('Jabatan Pemeriksa'),
                    ],
                ],
                [
                    'title' => 'A. Pemeriksaan Kebersihan Diri',
                    'fields' => [
                        $this->table('A. Pemeriksaan Kebersihan Diri', [
                            $this->col('Aspek Pemeriksaan', 'text', [], 'aspek'),
                            $this->col('Standar', 'text', [], 'standar'),
                            $this->col('Sesuai / Tidak Sesuai', 'select', ['Sesuai', 'Tidak Sesuai']),
                            $this->col('Catatan / Tindakan Korektif'),
                        ], [
                            ['aspek' => 'Kuku pendek, bersih, tanpa cat kuku', 'standar' => 'Wajib'],
                            ['aspek' => 'Tangan bebas luka terbuka / infeksi', 'standar' => 'Wajib'],
                            ['aspek' => 'Rambut tertutup oleh penutup kepala / hairnet', 'standar' => 'Wajib'],
                            ['aspek' => 'Tidak memakai perhiasan saat bekerja', 'standar' => 'Wajib'],
                            ['aspek' => 'Seragam dapur bersih dan lengkap', 'standar' => 'Wajib'],
                            ['aspek' => 'Tidak menggunakan parfum menyengat', 'standar' => 'Disarankan'],
                            ['aspek' => 'Tidak makan / merokok di area dapur', 'standar' => 'Wajib'],
                        ]),
                    ],
                ],
                [
                    'title' => 'B. Pemeriksaan Prosedur Cuci Tangan',
                    'fields' => [
                        $this->table('B. Pemeriksaan Prosedur Cuci Tangan', [
                            $this->col('Langkah Pemeriksaan', 'text', [], 'langkah'),
                            $this->col('Standar Pelaksanaan', 'text', [], 'standar'),
                            $this->col('Sesuai / Tidak Sesuai', 'select', ['Sesuai', 'Tidak Sesuai']),
                            $this->col('Catatan'),
                        ], [
                            ['langkah' => 'Mencuci tangan sebelum memulai pekerjaan', 'standar' => '20 detik dengan sabun & air mengalir'],
                            ['langkah' => 'Mencuci tangan setelah menangani bahan mentah', 'standar' => 'Segera setelah kontak'],
                            ['langkah' => 'Mencuci tangan setelah menggunakan toilet', 'standar' => 'Wajib setiap kali'],
                            ['langkah' => 'Menggunakan pengering tangan / tisu bersih', 'standar' => 'Tidak menggunakan kain lap'],
                            ['langkah' => 'Menggunakan hand sanitizer setelah mencuci tangan', 'standar' => 'Sesuai kebutuhan'],
                        ]),
                    ],
                ],
                [
                    'title' => 'C. Pemeriksaan Penggunaan APD',
                    'fields' => [
                        $this->table('C. Pemeriksaan Penggunaan APD', [
                            $this->col('Jenis APD', 'text', [], 'jenis'),
                            $this->col('Kondisi Standar', 'text', [], 'standar'),
                            $this->col('Digunakan / Tidak Digunakan', 'select', ['Digunakan', 'Tidak Digunakan']),
                            $this->col('Catatan'),
                        ], [
                            ['jenis' => 'Masker', 'standar' => 'Bersih & menutup hidung dan mulut'],
                            ['jenis' => 'Sarung tangan', 'standar' => 'Digunakan sesuai aktivitas (mis. bahan matang)'],
                            ['jenis' => 'Apron / Celemek', 'standar' => 'Tidak robek, dalam kondisi bersih'],
                            ['jenis' => 'Sepatu kerja / non-slip', 'standar' => 'Tertutup dan aman dari tumpahan'],
                            ['jenis' => 'Penutup kepala / hairnet', 'standar' => 'Menutupi seluruh rambut'],
                        ]),
                    ],
                ],
                [
                    'title' => 'D. Pemeriksaan Kondisi Kesehatan Petugas',
                    'fields' => [
                        $this->table('D. Pemeriksaan Kondisi Kesehatan Petugas', [
                            $this->col('Aspek Pemeriksaan', 'text', [], 'aspek'),
                            $this->col('Standar', 'text', [], 'standar'),
                            $this->col('Sesuai / Tidak Sesuai', 'select', ['Sesuai', 'Tidak Sesuai']),
                            $this->col('Catatan / Tindakan'),
                        ], [
                            ['aspek' => 'Suhu tubuh normal (36–37.5°C)', 'standar' => 'Diukur sebelum kerja'],
                            ['aspek' => 'Tidak mengalami batuk / flu / demam', 'standar' => 'Wajib'],
                            ['aspek' => 'Tidak ada luka terbuka di tangan / lengan', 'standar' => 'Wajib'],
                            ['aspek' => 'Tidak mengalami gangguan pencernaan (diare, mual)', 'standar' => 'Wajib'],
                            ['aspek' => 'Tidak sedang dalam pengobatan antibiotik tertentu', 'standar' => 'Disarankan'],
                        ]),
                    ],
                ],
                [
                    'title' => 'E. Rangkuman Pemeriksaan',
                    'fields' => [
                        $this->number('Total Poin Pemeriksaan'),
                        $this->number('Jumlah Sesuai Standar'),
                        $this->number('Jumlah Tidak Sesuai'),
                        $this->number('Persentase Kepatuhan (%)'),
                    ],
                ],
                [
                    'title' => 'Verifikasi & Tanda Tangan',
                    'fields' => [
                        $this->verificationTable('Verifikasi & Tanda Tangan', [
                            'Petugas yang Diperiksa', 'Petugas Pemeriksa', 'Koordinator Dapur',
                        ]),
                    ],
                ],
            ],
        ];
    }

    private function form05(): array
    {
        return [
            'category' => 'SDM',
            'code' => 'FRM-DU/08',
            'title' => 'Formulir Jadwal Kerja & Shift Petugas Dapur',
            'description' => 'Menyusun dan mendokumentasikan jadwal kerja, shift, dan pembagian area kerja tenaga dapur umum.',
            'purpose' => 'Formulir ini digunakan untuk menyusun dan mendokumentasikan jadwal kerja tenaga dapur umum, termasuk pembagian shift, tanggung jawab, serta rotasi tugas setiap harinya. Pencatatan ini memastikan bahwa kegiatan operasional berjalan lancar, personel dapur tidak kelelahan, dan seluruh area kerja tetap terkontrol secara efektif.',
            'instructions' => "1. Diisi oleh Koordinator Dapur sebelum awal minggu atau awal operasional baru.\n2. Nama dan jabatan seluruh petugas harus dicantumkan lengkap beserta waktu kerjanya.\n3. Setiap petugas wajib menandatangani daftar hadir saat mulai dan selesai bertugas.\n4. Perubahan jadwal harus disetujui oleh koordinator dan dicatat pada kolom keterangan.",
            'sections' => [
                [
                    'title' => 'A. Identitas Jadwal Kerja',
                    'fields' => [
                        $this->text('Periode Jadwal', true, 'Format: tanggal mulai s.d. tanggal selesai'),
                        $this->text('Lokasi Dapur / Posko', true),
                        $this->number('Jumlah Petugas Dapur'),
                        $this->text('Nama Koordinator Dapur', true),
                        $this->text('Kontak Darurat / PIC Lapangan'),
                    ],
                ],
                [
                    'title' => 'B. Jadwal Shift Petugas Dapur',
                    'fields' => [
                        $this->table('B. Jadwal Shift Petugas Dapur', [
                            $this->col('Nama Petugas'),
                            $this->col('Jabatan / Posisi'),
                            $this->col('Shift', 'select', ['Pagi', 'Siang', 'Malam']),
                            $this->col('Jam Masuk', 'time'),
                            $this->col('Jam Selesai', 'time'),
                            $this->col('Tugas Utama'),
                            $this->col('Tanda Tangan'),
                        ], required: true),
                    ],
                ],
                [
                    'title' => 'C. Pembagian Tugas & Area Kerja',
                    'fields' => [
                        $this->table('C. Pembagian Tugas & Area Kerja', [
                            $this->col('Area Dapur', 'text', [], 'area'),
                            $this->col('Nama Penanggung Jawab'),
                            $this->col('Tugas Utama', 'text', [], 'tugas'),
                            $this->col('Waktu Pengecekan', 'time'),
                            $this->col('Status Kebersihan / Kesiapan'),
                            $this->col('Paraf'),
                        ], [
                            ['area' => 'Area Persiapan Bahan', 'tugas' => 'Cuci, sortir, timbang bahan'],
                            ['area' => 'Area Memasak', 'tugas' => 'Masak, kontrol suhu'],
                            ['area' => 'Area Pengemasan', 'tugas' => 'Packing, pelabelan'],
                            ['area' => 'Area Pencucian', 'tugas' => 'Cuci alat masak & makan'],
                            ['area' => 'Area Penyimpanan', 'tugas' => 'Kontrol stok & suhu'],
                            ['area' => 'Area Distribusi', 'tugas' => 'Siapkan pengiriman & loading'],
                        ]),
                    ],
                ],
                [
                    'title' => 'D. Kondisi Kesehatan & Kesiapan Petugas',
                    'fields' => [
                        $this->table('D. Kondisi Kesehatan & Kesiapan Petugas', [
                            $this->col('Nama Petugas'),
                            $this->col('Suhu Tubuh (°C)', 'number'),
                            $this->col('Keluhan Kesehatan'),
                            $this->col('Layak Bertugas', 'select', ['Ya', 'Tidak']),
                            $this->col('Tanda Tangan'),
                        ]),
                    ],
                ],
                [
                    'title' => 'E. Catatan Perubahan Jadwal / Pergantian Shift',
                    'fields' => [
                        $this->table('E. Catatan Perubahan Jadwal / Pergantian Shift', [
                            $this->col('Nama Petugas'),
                            $this->col('Shift Awal', 'select', ['Pagi', 'Siang', 'Malam']),
                            $this->col('Shift Baru', 'select', ['Pagi', 'Siang', 'Malam']),
                            $this->col('Alasan Pergantian'),
                            $this->col('Disetujui Oleh'),
                            $this->col('Tanggal / Paraf'),
                        ]),
                    ],
                ],
                [
                    'title' => 'F. Pengesahan Jadwal',
                    'fields' => [
                        $this->verificationTable('F. Pengesahan Jadwal', [
                            'Koordinator Dapur',
                            'Kepala Dapur / Manajer Operasional',
                            'Penanggung Jawab Wilayah / Donatur (Jika Ada)',
                        ]),
                    ],
                ],
            ],
        ];
    }

    private function form06(): array
    {
        return [
            'category' => 'SDM',
            'code' => 'FRM-DU/09',
            'title' => 'Formulir Kehadiran Relawan / Tenaga Dapur',
            'description' => 'Mendokumentasikan kehadiran harian relawan dan tenaga dapur selama operasional berlangsung.',
            'purpose' => 'Formulir ini digunakan untuk mendokumentasikan kehadiran harian relawan dan tenaga dapur selama kegiatan operasional dapur umum berlangsung. Data ini penting untuk menilai tingkat keaktifan, tanggung jawab, serta kebutuhan penjadwalan dan evaluasi kinerja tenaga dapur secara keseluruhan.',
            'instructions' => "1. Diisi oleh Koordinator Relawan atau Petugas Administrasi Dapur setiap hari.\n2. Semua relawan wajib mengisi tanda tangan saat hadir dan pulang.\n3. Kolom \"Keterangan\" digunakan untuk mencatat alasan ketidakhadiran atau keterlambatan.\n4. Formulir dikumpulkan setiap akhir minggu untuk rekap absensi bulanan.",
            'sections' => [
                [
                    'title' => 'A. Identitas Umum',
                    'fields' => [
                        $this->text('Nama Dapur / Posko', true),
                        $this->text('Lokasi Operasional'),
                        $this->text('Periode Tanggal', true),
                        $this->text('Koordinator Relawan'),
                        $this->number('Total Relawan Terlibat'),
                    ],
                ],
                [
                    'title' => 'B. Daftar Kehadiran Relawan / Tenaga Dapur',
                    'fields' => [
                        $this->table('B. Daftar Kehadiran Relawan / Tenaga Dapur', [
                            $this->col('Nama Lengkap'),
                            $this->col('Jabatan / Peran'),
                            $this->col('Jam Datang', 'time'),
                            $this->col('Jam Pulang', 'time'),
                            $this->col('Tanda Tangan'),
                            $this->col('Keterangan'),
                        ], required: true),
                    ],
                ],
                [
                    'title' => 'C. Rekapitulasi Kehadiran Mingguan',
                    'fields' => [
                        $this->table('C. Rekapitulasi Kehadiran Mingguan', [
                            $this->col('Hari / Tanggal', 'text', [], 'hari'),
                            $this->col('Jumlah Relawan Hadir', 'number'),
                            $this->col('Jumlah Tidak Hadir', 'number'),
                            $this->col('Persentase Kehadiran (%)', 'number'),
                            $this->col('Catatan Penting'),
                        ], [
                            ['hari' => 'Senin'], ['hari' => 'Selasa'], ['hari' => 'Rabu'], ['hari' => 'Kamis'],
                            ['hari' => 'Jumat'], ['hari' => 'Sabtu'], ['hari' => 'Minggu'],
                        ]),
                    ],
                ],
                [
                    'title' => 'D. Evaluasi Kedisiplinan & Kinerja',
                    'fields' => [
                        $this->table('D. Evaluasi Kedisiplinan & Kinerja', [
                            $this->col('Aspek Penilaian', 'text', [], 'aspek'),
                            $this->col('Uraian / Catatan'),
                        ], [
                            ['aspek' => 'Ketepatan waktu hadir'],
                            ['aspek' => 'Kepatuhan terhadap SOP dapur'],
                            ['aspek' => 'Kerjasama tim & komunikasi'],
                            ['aspek' => 'Kepedulian terhadap kebersihan dan K3'],
                            ['aspek' => 'Catatan pelanggaran / teguran'],
                        ]),
                    ],
                ],
                [
                    'title' => 'E. Pengesahan',
                    'fields' => [
                        $this->verificationTable('E. Pengesahan', [
                            'Koordinator Relawan',
                            'Kepala Dapur / Manajer Operasional',
                            'Penanggung Jawab Lapangan / Donatur (Jika Ada)',
                        ]),
                    ],
                ],
            ],
        ];
    }

    private function form07(): array
    {
        return [
            'category' => 'Operasional',
            'code' => 'FRM-DU/12',
            'title' => 'Formulir Laporan Harian Operasional Dapur',
            'description' => 'Mendokumentasikan kegiatan harian dapur umum: produksi, distribusi, tenaga kerja, dan kondisi fasilitas.',
            'purpose' => 'Formulir ini digunakan untuk mendokumentasikan kegiatan harian dapur umum, termasuk jumlah produksi makanan, tenaga kerja yang bertugas, distribusi makanan, dan kondisi peralatan atau sarana dapur. Tujuannya agar operasional harian dapat dipantau, dievaluasi, dan dikendalikan secara efektif dan transparan.',
            'instructions' => "1. Diisi oleh Koordinator Dapur setiap akhir hari operasional.\n2. Cantumkan seluruh data kegiatan dapur mulai dari penerimaan bahan, proses produksi, penyajian, hingga distribusi.\n3. Pastikan semua kolom terisi lengkap dan sesuai fakta lapangan.\n4. Laporan ini wajib ditandatangani oleh Koordinator Dapur dan disetujui oleh Penanggung Jawab Operasional.",
            'sections' => [
                [
                    'title' => 'A. Identitas Dapur & Hari Operasional',
                    'fields' => [
                        $this->text('Nama Dapur / Posko', true),
                        $this->text('Lokasi Operasional'),
                        $this->date('Tanggal Operasional', true),
                        $this->select('Waktu Operasional', ['Pagi', 'Siang', 'Sore', 'Malam'], true),
                        $this->text('Nama Koordinator Dapur', true),
                        $this->text('Nama Penanggung Jawab'),
                    ],
                ],
                [
                    'title' => 'B. Produksi & Distribusi Makanan',
                    'fields' => [
                        $this->table('B. Produksi & Distribusi Makanan', [
                            $this->col('Jenis Makanan / Menu'),
                            $this->col('Jumlah Porsi Dihasilkan', 'number'),
                            $this->col('Jumlah Porsi Didistribusikan', 'number'),
                            $this->col('Sisa Porsi', 'number'),
                            $this->col('Tujuan Distribusi / Lokasi'),
                            $this->col('Keterangan'),
                        ], required: true),
                    ],
                ],
                [
                    'title' => 'C. Tenaga Dapur / Relawan Bertugas',
                    'fields' => [
                        $this->table('C. Tenaga Dapur / Relawan Bertugas', [
                            $this->col('Nama Petugas / Relawan'),
                            $this->col('Jabatan / Posisi'),
                            $this->col('Jam Masuk', 'time'),
                            $this->col('Jam Keluar', 'time'),
                            $this->col('Keterangan'),
                        ]),
                    ],
                ],
                [
                    'title' => 'D. Kondisi Fasilitas & Peralatan Dapur',
                    'fields' => [
                        $this->table('D. Kondisi Fasilitas & Peralatan Dapur', [
                            $this->col('Nama Peralatan / Fasilitas'),
                            $this->col('Kondisi', 'select', ['Baik', 'Rusak']),
                            $this->col('Tindakan / Catatan Perbaikan'),
                        ]),
                    ],
                ],
                [
                    'title' => 'E. Catatan Kebersihan & Sanitasi',
                    'fields' => [
                        $this->table('E. Catatan Kebersihan & Sanitasi', [
                            $this->col('Area Dapur', 'text', [], 'area'),
                            $this->col('Kondisi Kebersihan', 'select', ['Bersih', 'Kotor']),
                            $this->col('Keterangan / Tindakan'),
                        ], [
                            ['area' => 'Area Masak'],
                            ['area' => 'Area Cuci'],
                            ['area' => 'Penyimpanan Bahan'],
                            ['area' => 'Area Distribusi'],
                        ]),
                    ],
                ],
                [
                    'title' => 'F. Evaluasi & Catatan Tambahan',
                    'fields' => [
                        $this->table('F. Evaluasi & Catatan Tambahan', [
                            $this->col('Uraian / Kejadian'),
                            $this->col('Tindakan / Rencana Perbaikan'),
                            $this->col('Penanggung Jawab'),
                        ]),
                    ],
                ],
                [
                    'title' => 'G. Persetujuan & Tanda Tangan',
                    'fields' => [
                        $this->verificationTable('G. Persetujuan & Tanda Tangan', [
                            'Koordinator Dapur', 'Kepala Logistik / Penanggung Jawab Dapur',
                        ]),
                    ],
                ],
            ],
        ];
    }

    private function form08(): array
    {
        return [
            'category' => 'Operasional',
            'code' => 'CHK-DU/04',
            'title' => 'Checklist Peralatan Dapur (Kebersihan & Fungsi)',
            'description' => 'Memastikan seluruh peralatan dapur umum bersih, higienis, dan berfungsi baik sebelum digunakan.',
            'purpose' => 'Untuk memastikan semua peralatan dapur umum dalam keadaan bersih, higienis, dan berfungsi dengan baik sebelum digunakan, guna mendukung kelancaran operasional dan menjaga keamanan pangan.',
            'instructions' => "1. Pemeriksaan dilakukan setiap awal dan akhir shift kerja.\n2. Beri tanda (✓) pada kolom \"Baik\" jika kondisi memenuhi standar atau (✗) pada kolom \"Perlu Perbaikan\".\n3. Isi kolom \"Catatan/Tindakan\" jika ada peralatan rusak, hilang, atau perlu dibersihkan ulang.\n4. Checklist dikumpulkan setiap hari ke Koordinator Dapur untuk verifikasi.",
            'sections' => [
                [
                    'title' => 'Formulir Pemeriksaan Peralatan Dapur',
                    'fields' => [
                        // Catatan: kolom "Kondisi Fungsi" di PDF sumber punya makna berbeda
                        // per jenis peralatan (Berfungsi/Tidak, Tajam/Tumpul, Akurat/Tidak
                        // Akurat, dst.) -- dibiarkan sebagai teks bebas di sini, bukan
                        // dipaksakan jadi satu daftar pilihan gabungan yang membingungkan.
                        $this->table('Formulir Pemeriksaan Peralatan Dapur', [
                            $this->col('Nama Peralatan', 'text', [], 'nama'),
                            $this->col('Jumlah', 'number'),
                            $this->col('Kondisi Kebersihan', 'select', ['Bersih', 'Kotor']),
                            $this->col('Kondisi Fungsi'),
                            $this->col('Hasil', 'select', ['Baik', 'Perlu Perbaikan']),
                            $this->col('Catatan / Tindakan'),
                        ], [
                            ['nama' => 'Kompor Gas / Listrik'],
                            ['nama' => 'Wajan / Panci Besar'],
                            ['nama' => 'Sendok Sayur / Spatula / Centong'],
                            ['nama' => 'Pisau & Talenan'],
                            ['nama' => 'Blender / Food Processor'],
                            ['nama' => 'Oven / Pemanggang'],
                            ['nama' => 'Timbangan Dapur'],
                            ['nama' => 'Loyang / Cetakan'],
                            ['nama' => 'Piring, Gelas, Sendok Makan'],
                            ['nama' => 'Rice Cooker / Steamer'],
                            ['nama' => 'Kulkas / Freezer'],
                            ['nama' => 'Wastafel & Selang Air'],
                            ['nama' => 'Tempat Sampah Stainless / Plastik'],
                            ['nama' => 'APD (Sarung Tangan, Masker, Apron)'],
                            ['nama' => 'Lainnya'],
                        ]),
                    ],
                ],
                [
                    'title' => 'Rangkuman Hasil Pemeriksaan',
                    'fields' => [
                        $this->number('Total Peralatan Diperiksa'),
                        $this->number('Jumlah Peralatan Bersih & Berfungsi Baik'),
                        $this->number('Jumlah Peralatan Perlu Perbaikan'),
                        $this->text('Tindakan Perbaikan Dilakukan Oleh'),
                    ],
                ],
                [
                    'title' => 'Tanda Tangan Petugas & Verifikasi',
                    'fields' => [
                        $this->verificationTable('Tanda Tangan Petugas & Verifikasi', ['Petugas Pemeriksa', 'Koordinator Dapur']),
                    ],
                ],
            ],
        ];
    }

    private function form09(): array
    {
        return [
            'category' => 'Serah Terima',
            'code' => 'F-DU/14',
            'title' => 'Formulir Serah Terima Operasional Dapur Umum',
            'description' => 'Serah terima tanggung jawab operasional dapur umum antar petugas, mencakup kondisi fasilitas, bahan, dan stok.',
            'purpose' => null,
            'instructions' => null,
            'sections' => [
                [
                    'title' => 'A. Informasi Umum',
                    'fields' => [
                        $this->text('Nama Dapur Umum', true),
                        $this->text('Lokasi Dapur'),
                        $this->date('Tanggal Operasional', true),
                        $this->select('Jenis Serah Terima', ['Pembukaan Dapur', 'Penutupan Dapur'], true),
                        $this->text('Petugas Penanggung Jawab Lama'),
                        $this->text('Petugas Penanggung Jawab Baru'),
                    ],
                ],
                [
                    'title' => 'B. Kondisi Fasilitas & Peralatan',
                    'fields' => [
                        $this->table('B. Kondisi Fasilitas & Peralatan', [
                            $this->col('Item Pemeriksaan', 'text', [], 'item'),
                            $this->col('Kondisi', 'select', [
                                'Baik', 'Perlu Perbaikan', 'Lengkap', 'Kurang', 'Aman',
                                'Tersedia', 'Tidak Tersedia', 'Bersih', 'Perlu Dibersihkan',
                                'Siap Pakai', 'Perlu Pengisian',
                            ]),
                            $this->col('Keterangan'),
                        ], [
                            ['item' => 'Kebersihan Area Dapur'],
                            ['item' => 'Kondisi Peralatan Masak'],
                            ['item' => 'Fasilitas Listrik & Gas'],
                            ['item' => 'Fasilitas Air Bersih'],
                            ['item' => 'Tempat Penyimpanan Bahan'],
                            ['item' => 'APAR & Alat K3'],
                        ]),
                        $this->textarea('Catatan Tambahan'),
                    ],
                ],
                [
                    'title' => 'C. Kondisi Bahan & Stok',
                    'fields' => [
                        $this->table('C. Kondisi Bahan & Stok', [
                            $this->col('Jenis Bahan', 'text', [], 'jenis'),
                            $this->col('Jumlah Tersedia', 'number'),
                            $this->col('Kondisi', 'select', ['Layak', 'Tidak Layak', 'Ada', 'Tidak Ada']),
                            $this->col('Catatan'),
                        ], [
                            ['jenis' => 'Bahan Pokok (beras, lauk, sayur)'],
                            ['jenis' => 'Bahan Tambahan / Bumbu'],
                            ['jenis' => 'Air Minum & Minuman'],
                            ['jenis' => 'Bahan Sisa Produksi'],
                        ]),
                    ],
                ],
                [
                    'title' => 'D. Dokumentasi Pendukung',
                    'fields' => [
                        $this->checkbox('Dokumentasi Pendukung', [
                            'Checklist Kebersihan Dapur',
                            'Checklist K3 Dapur',
                            'Laporan Penggunaan Bahan Harian',
                            'Laporan Keuangan Sementara',
                            'Dokumen Foto (sebelum dan sesudah)',
                        ]),
                    ],
                ],
                [
                    'title' => 'E. Pernyataan Serah Terima',
                    'fields' => [
                        $this->table('E. Pernyataan Serah Terima', [
                            $this->col('Pihak', 'text', [], 'pihak'),
                            $this->col('Nama'),
                            $this->col('Jabatan'),
                            $this->col('Tanda Tangan'),
                            $this->col('Tanggal', 'date'),
                        ], [
                            ['pihak' => 'Pihak Penyerah'],
                            ['pihak' => 'Pihak Penerima'],
                        ]),
                    ],
                ],
                [
                    'title' => 'F. Validasi Koordinator',
                    'fields' => [
                        $this->text('Nama Koordinator Dapur', true),
                        $this->text('Tanda Tangan & Stempel'),
                        $this->date('Tanggal'),
                    ],
                ],
            ],
        ];
    }
}
