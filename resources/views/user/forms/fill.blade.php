<x-layouts.app :title="$form->title . ' - RKB Dapur'" :heading="$form->title" :subheading="'Kode: ' . $form->code . ' · Versi ' . $version->version_number">

    <div class="mb-3">
        @if ($draft?->isRevisionRequested())
            <a href="{{ route('user.my-submissions.show', $draft) }}" class="text-decoration-none small"><i class="bi bi-arrow-left me-1"></i>Kembali ke Detail Submission</a>
        @else
            <a href="{{ route('user.forms.show', $form) }}" class="text-decoration-none small"><i class="bi bi-arrow-left me-1"></i>Kembali ke Detail Form</a>
        @endif
    </div>

    @if ($draft?->isRevisionRequested())
        <div class="alert alert-warning border-0 shadow-sm small">
            <strong><i class="bi bi-arrow-counterclockwise me-1"></i>Form ini diminta untuk direvisi.</strong>
            <div class="mt-1">Catatan Admin: {{ $draft->review_notes }}</div>
            <div class="mt-1">Perbaiki isian lalu klik <strong>Submit</strong> untuk mengirim ulang.</div>
        </div>
    @elseif ($draft)
        <div class="alert alert-info border-0 shadow-sm small">
            <i class="bi bi-arrow-repeat me-1"></i>
            Anda melanjutkan draft yang belum selesai. Klik <strong>Simpan Draft</strong> kapan saja untuk menyimpan
            progres, atau <strong>Submit</strong> setelah semua bagian wajib terisi.
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm">
            <p class="fw-semibold mb-2">Periksa kembali isian berikut:</p>
            <ul class="mb-0 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($form->purpose || $form->instructions)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                @if ($form->purpose)
                    <h2 class="h6 fw-bold mb-1">Tujuan</h2>
                    <p class="text-muted small mb-3">{{ $form->purpose }}</p>
                @endif
                @if ($form->instructions)
                    <h2 class="h6 fw-bold mb-1">Petunjuk Pengisian</h2>
                    <p class="text-muted small mb-0" style="white-space: pre-line;">{{ $form->instructions }}</p>
                @endif
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('user.forms.submit', $form) }}">
        @csrf

        @foreach ($version->sections as $section)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h2 class="h6 fw-bold mb-3">{{ $section->title }}</h2>
                    @if ($section->description)
                        <p class="text-muted small mb-3">{{ $section->description }}</p>
                    @endif

                    @forelse ($section->fields as $field)
                        @include('user.forms.fields.' . $field->field_type, [
                            'field' => $field,
                            'existingValue' => optional($existingAnswers->get($field->id))->value,
                            'existingRows' => $existingTableRows->get($field->id, collect()),
                        ])
                    @empty
                        <p class="text-muted small mb-0">Belum ada field di bagian ini.</p>
                    @endforelse
                </div>
            </div>
        @endforeach

        <div class="d-flex gap-2 mb-4">
            <button type="submit" name="intent" value="draft" class="btn btn-outline-secondary">
                <i class="bi bi-save me-1"></i> Simpan Draft
            </button>
            <button type="submit" name="intent" value="submit" class="btn btn-brand">
                <i class="bi bi-send me-1"></i> Submit
            </button>
        </div>
    </form>

    @push('scripts')
    <script>
        (function () {
            // Menetapkan ulang atribut name setiap input pada baris Dynamic Table
            // berdasarkan POSISI SAAT INI di dalam DOM (bukan counter yang terus
            // bertambah). Ini penting: kalau ada baris yang dihapus lalu baris baru
            // ditambahkan, counter yang terus naik akan menyisakan celah nomor index
            // (mis. 0,1,3 karena 2 pernah dihapus) yang bisa membuat data yang
            // dikirim tidak berurutan/rusak. Reindex penuh dari posisi DOM saat ini
            // menghindari masalah itu sepenuhnya.
            function reindexDynamicTable(table) {
                var fieldId = table.dataset.fieldId;
                var fixedCount = parseInt(table.dataset.fixedRows || '0', 10);
                var rows = table.querySelectorAll('tbody tr[data-dynamic-row]');

                rows.forEach(function (row, i) {
                    var rowIndex = fixedCount + i;
                    row.querySelectorAll('[data-col]').forEach(function (input) {
                        input.name = 'answers[' + fieldId + '][rows][' + rowIndex + '][' + input.dataset.col + ']';
                    });
                });
            }

            // Reindex semua tabel begitu halaman dimuat -- baris kosong awal
            // sengaja dirender tanpa atribut name terisi (lihat komentar di
            // dynamic_table.blade.php), jadi harus diisi sekali di sini dulu.
            document.querySelectorAll('table[data-field-id]').forEach(reindexDynamicTable);

            document.querySelectorAll('.add-dynamic-row-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var table = document.getElementById(btn.dataset.table);
                    var template = document.getElementById(btn.dataset.table + '-tmpl');
                    var clone = template.content.cloneNode(true);
                    table.querySelector('tbody').appendChild(clone);
                    reindexDynamicTable(table);
                });
            });

            document.addEventListener('click', function (e) {
                var removeBtn = e.target.closest('.remove-dynamic-row-btn');
                if (removeBtn) {
                    var table = removeBtn.closest('table[data-field-id]');
                    removeBtn.closest('tr').remove();
                    reindexDynamicTable(table);
                }
            });

            // Guard double-submit: klik ganda pada Submit/Simpan Draft (mis.
            // tidak sabar menunggu, atau koneksi lambat) bisa mengirim dua
            // request sebelum yang pertama selesai. Menonaktifkan KEDUA
            // tombol begitu form disubmit mencegah ini di sisi browser --
            // dipadukan dengan lockForUpdate() di FormSubmissionService untuk
            // sisi server (lihat komentar di sana).
            //
            // PENTING: menonaktifkan tombol SECARA LANGSUNG (synchronous) di
            // dalam event submit adalah bug -- per spesifikasi HTML, elemen
            // yang disabled dikecualikan saat browser menyusun data form yang
            // dikirim. Tombol yang baru diklik itu sendiri (yang membawa
            // name="intent" value="submit"/"draft") jadi ikut dikecualikan
            // SEBELUM sempat terkirim, dan `intent` hilang total dari
            // request -- FormSubmissionController membaca itu sebagai draft
            // (nilai default), APAPUN tombol yang diklik. Ini persis kenapa
            // "Submit" sebelumnya selalu berakhir sebagai draft. Diperbaiki
            // dengan menunda penonaktifan lewat setTimeout(..., 0): baru
            // jalan di tick berikutnya, setelah browser selesai membaca
            // nilai tombol untuk dikirim.
            var submitForm = document.querySelector('form[action*="/submit"]');
            if (submitForm) {
                submitForm.addEventListener('submit', function () {
                    setTimeout(function () {
                        submitForm.querySelectorAll('button[type="submit"]').forEach(function (btn) {
                            btn.disabled = true;
                        });
                    }, 0);
                });
            }
        })();
    </script>
    @endpush

</x-layouts.app>
