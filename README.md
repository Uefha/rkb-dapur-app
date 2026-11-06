# RKB Dapur — Platform Digitalisasi Form Dapur Umum

**Sampai dengan Phase 5: Submission (Draft, Submit, Validasi)**
Laravel 12 · PHP 8.3+ · MySQL · Bootstrap 5 (CDN, tanpa build step)

---

## 1. Perbaikan Penting di Rilis Ini (baca dulu sebelum setup)

**Bug migration "2 tabel users" sudah diperbaiki.** Sebelumnya `roles` dan `users` berada di file migration terpisah dengan nama yang **berbeda** dari bawaan `laravel new`. Kalau proses timpa-file tidak menghapus dulu migration bawaan Laravel, hasilnya ada dua migration berbeda nama yang sama-sama membuat tabel `users` — migration kedua yang jalan pasti gagal dengan error "Table 'users' already exists".

Diperbaiki dengan menggabungkan `roles` + `users` (+ `password_reset_tokens` + `sessions`, seperti bawaan Laravel) jadi **satu file bernama persis `0001_01_01_000000_create_users_table.php`**, dan `cache`/`jobs` juga dikembalikan ke nama file bawaan Laravel yang persis. Dengan ini, proses "timpa saat diminta" apa pun (Finder/Explorer drag-drop, `cp -f`, dsb.) akan selalu **menggantikan total**, bukan menambah file baru yang bentrok — bug ini tidak akan bisa terulang lagi selama migration lain juga mengikuti pola nama file yang sama saat overlap dengan bawaan Laravel.

**Kalau instalasi Anda sebelumnya sudah terlanjur error di tengah jalan:** hapus total isi folder `database/migrations` di project Anda, lalu salin ulang seluruh folder `database/migrations` dari zip ini (bukan cuma file yang berubah), kemudian `php artisan migrate:fresh --seed`.

---

## 2. Yang Sudah Aktif

### Phase 1 — Setup, Auth, Role
Login/logout, reset password, verifikasi email, role `admin`/`user`, middleware `role:*`, `PreventBackHistory`, `ActivityLogService`, halaman profil. Registrasi publik dinonaktifkan; akun user dibuat dan dinonaktifkan Admin melalui menu Manajemen User.

### Phase 2 — Form Categories, Form Management, Builder Dasar
CRUD Kategori Form, CRUD metadata Form, publish/unpublish/archive/versioning/duplicate, Form Builder (13 tipe field dasar + opsi Select/Radio/Checkbox), immutability versi published.

### Phase 3 — Dynamic Table & 9 Form Asli
Tipe field Dynamic Table (kolom custom + baris standar/checklist tetap), 9 form asli dari PDF sumber sudah ter-seed dan published.

### Phase 4 — Form Renderer & Katalog User
- **Katalog `/forms`** — daftar form published dengan search (nama/kode), filter kategori, sort (nama A-Z/Z-A/terbaru), pagination.
- **Halaman detail `/forms/{form}`** — nama, kode, kategori, versi, tujuan, petunjuk pengisian, tombol "Isi Form".
- **Form Renderer `/forms/{form}/fill`** — merender seluruh section & field dari `form_version` yang sedang published, mendukung **ke-14 tipe field** yang sudah ada di sistem (Text, Textarea, Number, Date, Time, DateTime, Email, Select, Radio, Checkbox, Yes/No, Static Text, Heading, Dynamic Table).
- **Dynamic Table di sisi User** — baris standar (checklist tetap) ditampilkan sebagai teks terkunci, kolom lain tetap bisa diisi; tabel bebas (tanpa baris standar) dimulai dengan beberapa baris kosong dan bisa ditambah tanpa batas lewat tombol "+ Tambah Baris". Penambahan/penghapusan baris memakai **reindex berbasis posisi DOM saat ini** (bukan counter yang terus bertambah), supaya index tidak pernah bolong setelah ada baris yang dihapus.
- Nilai baris standar (checklist tetap) **sengaja tidak dikirim lewat input** di sisi User — nilainya digabungkan dari struktur `form_version` di sisi server saat menyimpan (lihat Phase 5 di bawah), supaya nilai yang sudah ditentukan Admin tidak bisa diubah lewat devtools sebelum data sampai ke server.

### Phase 5 — Submission: Draft, Submit, Validasi
- **Satu endpoint, dua intent** — `POST /forms/{form}/submit` menerima `intent=draft` atau `intent=submit` dari tombol yang berbeda (`formaction`/`name="intent"`), diproses `FormSubmissionController` lalu `FormSubmissionService`.
- **Draft otomatis tersimpan & bisa dilanjutkan** — `FormFillController` mendeteksi draft aktif milik User untuk form yang sama dan mengisi ulang seluruh field (termasuk baris Dynamic Table) begitu halaman Isi Form dibuka lagi. Submission yang sudah submitted/reviewed/approved/rejected tidak pernah mengganggu pengisian baru (form operasional ini memang diisi berulang, mis. harian/per-shift).
- **Validasi dinamis** dibangun dari `field_type` + `is_required` tiap field (bukan hardcode per form): required hanya ditegakkan saat `intent=submit` (draft boleh belum lengkap), tipe number/email/date/time divalidasi sesuai formatnya, dan pilihan Select/Radio/Checkbox divalidasi terhadap opsi yang sungguh terdaftar. Dynamic Table sengaja belum divalidasi per-kolom di fase ini (dicatat sebagai keterbatasan, bukan lupa).
- **Data tidak pernah hilang saat validasi gagal** — hasil `Validator::validate()` HANYA dipakai untuk menolak input yang salah (Laravel otomatis redirect back dengan pesan error + input lama). Yang benar-benar disimpan selalu `$request->input('answers')` mentah, karena `Validator::validated()` di Laravel cuma mengembalikan key yang punya rule eksplisit — seluruh isi Dynamic Table (tidak divalidasi per-kolom di atas) akan senyap terbuang kalau hasil `validated()` yang dipakai untuk simpan.
- **"Form Saya" (`/my-submissions`)** — riwayat submission User sendiri, filter per status (Draft/Menunggu Verifikasi/Ditinjau/Disetujui/Ditolak), dan halaman detail read-only per submission (termasuk catatan penolakan kalau ada).
- Setiap penyimpanan (draft maupun submit) menghapus seluruh jawaban & baris lama milik submission itu lalu menulis ulang dari nol — lebih sederhana dan aman daripada diffing partial update.

**Belum ada** (menyusul roadmap): Repeater/File/Image/Signature/Calculated Field & conditional logic (Phase 8).

### Phase 6 — Verifikasi/Review Admin & Hapus Draft User
- **Hapus Draft (User)** — `DELETE /my-submissions/{submission}`, hanya untuk submission berstatus draft milik sendiri (submission yang sudah dikirim adalah catatan resmi, tidak bisa dihapus User).
- **Halaman Submission (Admin, `/admin/submissions`)** — daftar seluruh submission yang sudah dikirim (draft User tidak ikut tampil secara default), filter per status dan per form.
- **Alur review**: Tandai Sedang Ditinjau (opsional) → Setujui / Tolak. Tolak **wajib** diisi catatan alasannya (divalidasi `SubmissionReviewService`); Setujui catatannya opsional. Submission `approved` dan submission yang benar-benar ditolak terkunci; permintaan revisi dapat dikirim ulang oleh User.
- **Permintaan revisi** — Admin dapat mengembalikan submission ke User dengan catatan wajib. User membuka kembali submission yang sama, memperbaiki jawaban, lalu mengirim ulang untuk ditinjau kembali.
- **Hapus submission** — Admin dapat menghapus permanen submission beserta jawaban dan baris Dynamic Table terkait.
- Dashboard Admin sekarang menampilkan jumlah **Menunggu Verifikasi** (link langsung ke daftar submission yang perlu ditinjau).

**Belum ada** (menyusul roadmap): Repeater/File/Image/Signature/Calculated Field & conditional logic (Phase 8).

### Ekspor & filter submission
- Admin dapat memfilter submission berdasarkan rentang tanggal submit, pengisi, form, kategori, status, dan pencarian ID/nama/email/kode.
- Hasil sesuai filter dapat diunduh sebagai Excel, Word, atau PDF. Ekspor memuat metadata, jawaban field, pilihan, tabel dinamis, status review, dan catatan review.

### Pengelolaan akun & akses form
- Admin dapat menambah/mengubah akun user dan mengaktifkan atau menonaktifkannya melalui `/admin/users`.
- Dari Manajemen Form, Admin dapat mengatur setiap form agar tersedia bagi semua user atau hanya user tertentu. Akses ini diperiksa pada katalog, halaman detail, pengisian, dan endpoint penyimpanan.
- Form yang sudah ada mempertahankan perilaku sebelumnya (akses semua user) setelah migration dijalankan. Akun baru langsung terverifikasi dan dapat login.

---

## 3. Cara Menjalankan (Lokal — VS Code)

```bash
composer create-project laravel/laravel rkb-dapur-app "^12.0"
cd rkb-dapur-app
composer require laravel/breeze --dev

# PENTING: hapus dulu isi folder database/migrations bawaan sebelum menyalin
# punya kita, supaya tidak ada file lama yang tertinggal:
rm -f database/migrations/*.php

# Baru salin app/, database/, resources/, routes/, config/, bootstrap/,
# dan .env.example dari zip ini ke folder rkb-dapur-app

composer install
cp .env.example .env
php artisan key:generate
# sesuaikan DB_DATABASE/DB_USERNAME/DB_PASSWORD di .env, buat DB kosong "rkb_dapur"

php artisan migrate --seed
php artisan serve
```

### Kredensial default

| Role | Email | Password |
|---|---|---|
| Admin | `admin@rkb-dapur.test` | `password` |
| User | `petugas@rkb-dapur.test` | `password` |

### Cara test Phase 4 (termasuk verifikasi perbaikan)

1. Login sebagai **petugas@rkb-dapur.test** → klik **Form Tersedia** di sidebar (atau kartu statistik di dashboard) → harus tampil 9 form.
2. Coba search (mis. ketik "suhu"), filter kategori "Penyimpanan", ganti sort ke "Terbaru".
3. Klik salah satu form → halaman detail tampil (tujuan, petunjuk pengisian) → klik **Isi Form**.
4. **Khusus verifikasi perbaikan bug terbaru:** buka **"Checklist Peralatan Dapur"** (atau form lain apa pun, karena hampir semua kena) → halaman harus tampil normal tanpa Error 500, termasuk kolom "Kondisi Kebersihan" dan "Hasil" yang bertipe Select harus muncul sebagai dropdown dengan pilihannya.
5. Coba form yang punya Dynamic Table dengan baris standar (mis. "Checklist Peralatan Dapur") — pastikan 15 nama peralatan tampil terkunci, kolom lain tetap bisa diisi.
6. Coba form dengan tabel bebas (mis. "Formulir Penerimaan Bahan Makanan") — klik **+ Tambah Baris** beberapa kali, lalu hapus salah satu baris di tengah, lalu tambah lagi — pastikan tidak ada error console dan baris baru tetap berfungsi normal (ini yang diuji oleh mekanisme reindex).
7. Pastikan tombol **Simpan Draft** / **Submit** terlihat non-aktif (abu-abu) — ini memang disengaja sampai Phase 5.
8. **Khusus verifikasi perbaikan Bootstrap JS:** buka **Manajemen Form** → Builder salah satu form yang belum published → klik **Tambah Section**, modal harus terbuka. Coba juga klik **Tambah Field** di dalam sebuah section, dan tombol **Aksi lain** (dropdown) di halaman daftar Manajemen Form — semuanya harus responsif.
9. **Khusus verifikasi perbaikan Kategori & Publish/Unpublish:** hapus (soft-delete) satu form, lalu coba hapus kategorinya — harus muncul pesan error yang jelas, bukan Error 500. Lalu: Publish sebuah form → Unpublish → Publish lagi (tanpa "Buat Versi Baru") — harus berhasil langsung tanpa pesan "Tidak ada versi draft".
10. **Khusus verifikasi perbaikan badge Status:** buka **Manajemen Form** — badge di kolom Status (Published/Draft/Archived) harus terbaca jelas dengan warna latar hijau/kuning/hitam dan teks kontras, bukan teks putih tak terlihat.

### Cara test Phase 5

1. Login sebagai **petugas@rkb-dapur.test** → buka salah satu form (mis. "Formulir Penerimaan Bahan Makanan") → klik **Isi Form**. Tombol **Simpan Draft**/**Submit** sekarang harus aktif (tidak abu-abu lagi).
2. Isi sebagian field saja, klik **Simpan Draft** → harus redirect ke halaman detail submission berstatus **Draft**.
3. Buka lagi form yang sama lewat **Form Tersedia** → klik **Isi Form** lagi → seluruh isian sebelumnya (termasuk baris Dynamic Table yang sempat ditambah) harus otomatis terisi kembali (melanjutkan draft, bukan mulai kosong).
4. Coba klik **Submit** tanpa mengisi field yang wajib → harus muncul pesan error di atas form, dan isian yang SUDAH diketik tetap ada (tidak hilang).
5. Lengkapi field wajib, klik **Submit** → status berubah jadi **Menunggu Verifikasi**, dan submission ini tidak lagi muncul sebagai draft yang bisa dilanjutkan kalau form yang sama dibuka ulang (submission baru akan dibuat, bukan menimpa yang sudah submit).
6. Buka **Form Saya** di sidebar → submission yang baru dibuat harus muncul, coba filter per status.
7. **Khusus verifikasi perbaikan nilai "0" di tabel:** isi salah satu kolom angka Dynamic Table dengan angka **0** (mis. "Jumlah Ditolak"), submit, lalu buka detail submission-nya — nilai **0** harus tetap terlihat sebagai "0", bukan "—".
8. **Khusus verifikasi guard double-submit:** buka DevTools → Network, isi form, klik **Submit** dua kali secepat mungkin — harus hanya ada satu submission yang tercatat (tombol akan langsung nonaktif setelah klik pertama).
9. **Khusus verifikasi perbaikan Submit tersimpan sebagai draft:** isi form apa saja sampai field wajibnya lengkap, klik **Submit** (bukan Simpan Draft) — pastikan hasilnya berstatus **"Menunggu Verifikasi"**, bukan "Draft". Login sebagai Admin, buka **Submission**, pastikan submission ini muncul di sana.

### Cara test Phase 6

1. Sebagai User, buat sebuah draft (Simpan Draft, jangan Submit) → buka **Form Saya** → detail draft tersebut → klik **Hapus Draft** → konfirmasi → draft harus hilang dari daftar.
2. Submit satu form sampai berstatus "Menunggu Verifikasi".
3. Login sebagai **admin@rkb-dapur.test** → buka **Submission** di sidebar (atau kartu "Menunggu Verifikasi" di dashboard) → submission tadi harus muncul (draft User lain tidak ikut tampil).
4. Buka detail submission → klik **Setujui** → status berubah jadi "Disetujui", tombol aksi hilang (tidak bisa diproses ulang).
5. Submit form lain → di detailnya klik **Tolak** tanpa mengisi catatan → harus ditolak validasi (catatan wajib). Isi catatan → Tolak lagi → berhasil, status jadi "Ditolak", dan catatannya kelihatan.
6. Login lagi sebagai User → buka **Form Saya** → submission yang ditolak tadi harus menampilkan catatan penolakannya.

---

## 4. Audit Sebelum Pengiriman

Audit rilis ini dipicu laporan **`TypeError: FormValidationService::inRuleForOptions(): Return value must be of type Illuminate\Contracts\Validation\Rule, Illuminate\Validation\Rules\In returned`** saat Submit — muncul lagi setelah perbaikan rilis sebelumnya, dengan pesan yang mirip tapi nama interface berbeda.

**Perbaikan rilis sebelumnya salah, dan ini kenapa:** dugaan sebelumnya adalah `Rule::in()` mengembalikan instance dari `Illuminate\Contracts\Validation\Rule` (interface lama, dengan method `passes()`/`message()`), jadi return type method diubah ke situ. Ternyata salah juga — di Laravel 12.69 yang terpasang, `Rule::in()` mengembalikan objek yang implement `Illuminate\Contracts\Validation\ValidationRule` (kontrak **baru**, method `validate()`), bukan kontrak lama itu. Laravel punya beberapa kontrak rule berbeda antar versi (`Rule` lama, `ValidationRule` baru, `InvokableRule`), dan kelas konkret yang dipakai `Rule::in()` bisa berbeda antar versi framework tanpa itu dianggap breaking change oleh Laravel — karena Validator-nya sendiri menerima semua bentuk itu. Masalahnya PHP tetap butuh SATU nama tipe pasti untuk return type method ini, dan menebak nama interface yang tepat ternyata rapuh — dua kali salah tebak berturut-turut.

**Perbaikan kali ini tidak menebak lagi.** Return type method `inRuleForOptions()` diganti jadi `mixed` — diterima Validator Laravel apa pun bentuk konkret objek rule yang sebenarnya dikembalikan, sehingga aman terhadap perubahan versi Laravel ke depan tanpa perlu tahu persis interface mana yang dipakai. Sudah dibuktikan lewat simulasi terisolasi (beberapa kelas rule buatan yang berbeda-beda, semuanya lolos tanpa TypeError) dan disapu ulang ke seluruh codebase — tidak ada return type Rule/ValidationRule sempit lain yang tersisa.

<details>
<summary><strong>Riwayat audit fase-fase sebelumnya (klik untuk lihat)</strong></summary>

-1. **TypeError return type `inRuleForOptions()` (percobaan pertama, salah).** Diubah dari `Illuminate\Validation\Rule` (kelas factory statis, bukan induk) ke `Illuminate\Contracts\Validation\Rule` (interface lama) — masih salah, lihat penjelasan di atas.
0. **Submit tersimpan sebagai draft.** Guard anti-klik-ganda menonaktifkan tombol secara synchronous di event `submit` — elemen `disabled` dikecualikan browser saat menyusun data terkirim, jadi `name="intent" value="submit"` pada tombol yang baru diklik ikut hilang. Diperbaiki dengan menunda penonaktifan lewat `setTimeout(fn, 0)`.
1. **ParseError `unexpected token "endif"` di `user/submissions/show.blade.php`** — sudah tidak ada di kode, ikut terselesaikan lewat pemecahan jadi partial `scalar-answer`/`dynamic-table-answer` yang dipakai bersama User & Admin.
2. **Migration `users` duplikat (bug fatal, dilaporkan langsung).** Dijelaskan lengkap di bagian 1 README ini.
3. **`@push('scripts')` tidak akan pernah tampil.** Layout belum punya `@stack('scripts')` sama sekali. Ditambahkan sebelum penutup `</body>`.
4. **Pagination tampil rusak/tanpa gaya.** Laravel default merender markup Tailwind, padahal proyek ini 100% Bootstrap 5. Ditambahkan `Paginator::useBootstrapFive()`.
5. **Halaman "Form Tersedia" tidak ada di navigasi sidebar User.** Ditambahkan link di sidebar + dashboard User diperbarui jadi statistik nyata.
6. **Error 500 "Cannot access offset of type string on string" di halaman Isi Form.** Komponen `<x-dynamic-table-cell>` salah mengasumsikan opsi kolom Select berbentuk array asosiatif `{label, value}`, padahal array string polos. Berdampak ke 22 kolom di 8 dari 9 form asli.
7. **Modal/dropdown Bootstrap tidak berfungsi sama sekali di seluruh aplikasi.** `bootstrap.bundle.min.js` tidak pernah dimuat sejak Phase 2. Ditambahkan di kedua layout.
8. **Error 500 (FK constraint) saat hapus Kategori yang "terlihat" tidak dipakai.** Guard `exists()` mengecualikan form yang sudah soft-delete, padahal baris fisiknya tetap memblokir FK. Diperbaiki dengan `withTrashed()`.
9. **"Tidak ada versi draft yang bisa dipublish" setelah Unpublish.** `publishForm()` keliru selalu mensyaratkan versi draft. Diperbaiki dengan membedakan reaktivasi versi published dari publish versi draft baru.
10. **Badge Status tidak terlihat (putih di atas putih).** `@class([...])` dipakai setelah `class="badge` sudah terbuka, menghasilkan dua atribut `class` — browser cuma pakai yang pertama (polos, tanpa warna).
11. **Nilai kolom Dynamic Table `0` tampil sebagai "—".** Elvis operator (`?:`) menganggap string `"0"` falsy. Diperbaiki dengan pengecekan eksplisit.
12. **Celah race condition klik ganda Submit/Simpan Draft.** Cek-draft dan tulis-jawaban digabung jadi satu transaksi dengan `lockForUpdate()`, dipadukan guard nonaktif-tombol di browser (guard inilah yang kemudian punya bug regresi sendiri, lihat item 0 di atas).

</details>

---

## 5. Langkah Selanjutnya

**Berikutnya:** Repeater/File/Image/Signature/Calculated Field & conditional logic (Phase 8).
untuk merecovery php artisan admin:recover