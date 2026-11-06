@php
    $columns = $field->config['columns'] ?? [];
    $defaultRows = $field->config['default_rows'] ?? [];
    $tableId = 'dyntable-' . $field->id;

    // Bangun peta [rowIndex => [columnKey => value]] dari draft yang sudah
    // tersimpan (kalau sedang melanjutkan draft).
    $existingRowsData = [];
    foreach ($existingRows as $row) {
        $cells = [];
        foreach ($row->answers as $answer) {
            $cells[$answer->column_key] = $answer->value;
        }
        $existingRowsData[$row->row_index] = $cells;
    }

    // old() untuk SELURUH struktur rows dipakai kalau validasi baru saja
    // gagal -- ini mencerminkan persis apa yang User kirim sebelumnya
    // (termasuk baris yang sempat ditambah/dihapus lewat JS), jadi lebih
    // diutamakan daripada draft tersimpan yang mungkin sudah agak lama.
    $rowsData = old("answers.{$field->id}.rows", $existingRowsData);

    // Baris tambahan (indeks >= jumlah baris standar) yang perlu dirender
    // sebagai <tr> nyata dengan nilai terisi, bukan baris kosong template.
    $extraRowIndexes = collect(array_keys($rowsData))
        ->map(fn ($i) => (int) $i)
        ->filter(fn ($i) => $i >= count($defaultRows))
        ->sort()
        ->values();
@endphp

<div class="mb-4">
    <label class="form-label small fw-semibold d-block">
        {{ $field->label }}
        @if ($field->is_required)<span class="text-danger">*</span>@endif
    </label>

    <div class="table-responsive border rounded">
        <table class="table table-bordered table-sm align-middle mb-0" id="{{ $tableId }}"
               data-field-id="{{ $field->id }}" data-fixed-rows="{{ count($defaultRows) }}">
            <thead class="table-light">
                <tr>
                    @foreach ($columns as $column)
                        <th class="small">{{ $column['label'] }}</th>
                    @endforeach
                    <th style="width: 36px;"></th>
                </tr>
            </thead>
            <tbody>
                {{-- Baris standar/tetap (mis. daftar peralatan pada Checklist Peralatan
                     Dapur). Nilai kolom yang sudah ditentukan admin TIDAK dikirim lewat
                     input tersembunyi -- ditampilkan sebagai teks saja. Nilai aslinya
                     digabungkan dari struktur form_version di FormSubmissionService,
                     supaya nilai baris tetap tidak bisa dimanipulasi lewat devtools. --}}
                @foreach ($defaultRows as $rowIndex => $defaultRow)
                    <tr>
                        @foreach ($columns as $column)
                            @php($isFixed = array_key_exists($column['key'], $defaultRow))
                            <td data-label="{{ $column['label'] }}">
                                @if ($isFixed)
                                    <span class="small fw-semibold">{{ $defaultRow[$column['key']] }}</span>
                                @else
                                    <x-dynamic-table-cell :column="$column"
                                        :name="'answers['.$field->id.'][rows]['.$rowIndex.']['.$column['key'].']'"
                                        :value="$rowsData[$rowIndex][$column['key']] ?? null" />
                                @endif
                            </td>
                        @endforeach
                        <td class="text-center text-muted small" data-label="Aksi">—</td>
                    </tr>
                @endforeach

                {{-- Baris tambahan bebas -- jumlahnya tidak dibatasi, User bisa
                     menambah lewat tombol di bawah. Kalau sedang melanjutkan draft
                     atau baru saja gagal validasi, baris yang sudah ada ikut
                     dirender dengan nilainya; kalau form benar-benar baru, tampil
                     beberapa baris kosong untuk mulai diisi. --}}
                @forelse ($extraRowIndexes as $rowIndex)
                    <tr data-dynamic-row>
                        @foreach ($columns as $column)
                            <td data-label="{{ $column['label'] }}">
                                <x-dynamic-table-cell :column="$column" :name="''"
                                    :value="$rowsData[$rowIndex][$column['key']] ?? null" />
                            </td>
                        @endforeach
                        <td class="text-center" data-label="Aksi">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-dynamic-row-btn" title="Hapus baris">&times;</button>
                        </td>
                    </tr>
                @empty
                    @for ($i = 0; $i < ($defaultRows ? 1 : 3); $i++)
                        <tr data-dynamic-row>
                            @foreach ($columns as $column)
                                <td data-label="{{ $column['label'] }}">
                                    <x-dynamic-table-cell :column="$column" :name="''" />
                                </td>
                            @endforeach
                            <td class="text-center" data-label="Aksi">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-dynamic-row-btn" title="Hapus baris">&times;</button>
                            </td>
                        </tr>
                    @endfor
                @endforelse
            </tbody>
        </table>
    </div>

    <template id="{{ $tableId }}-tmpl">
        <tr data-dynamic-row>
            @foreach ($columns as $column)
                <td data-label="{{ $column['label'] }}">
                    <x-dynamic-table-cell :column="$column" :name="''" />
                </td>
            @endforeach
            <td class="text-center" data-label="Aksi">
                <button type="button" class="btn btn-sm btn-outline-danger remove-dynamic-row-btn" title="Hapus baris">&times;</button>
            </td>
        </tr>
    </template>

    <button type="button" class="btn btn-sm btn-outline-secondary mt-2 add-dynamic-row-btn" data-table="{{ $tableId }}">
        <i class="bi bi-plus-lg me-1"></i> Tambah Baris
    </button>

    @if ($field->help_text)
        <div class="form-text">{{ $field->help_text }}</div>
    @endif
</div>
