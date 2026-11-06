@php
    // uniqid() dipanggil ulang setiap kali partial ini di-include (baik untuk
    // modal Tambah Field per section, maupun tiap modal Edit Field per field),
    // sehingga id elemen tidak pernah bentrok walau banyak modal ada sekaligus
    // di satu halaman (semua modal dirender inline, bukan lazy-loaded).
    $uid = 'f' . ($field->id ?? 'new') . '-' . uniqid();
    $existingOptions = $field?->options ?? collect();
    $existingColumns = $field?->tableColumns() ?? [];
@endphp

<div class="modal-header">
    <h5 class="modal-title">{{ $field ? 'Edit Field' : 'Tambah Field' }}</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <div class="mb-3">
        <label class="form-label small fw-semibold">Tipe Field</label>
        <select name="field_type" class="form-select field-type-select @error('field_type') is-invalid @enderror" required>
            <option value="">— Pilih Tipe —</option>
            @foreach (\App\Models\FormField::PHASE3_TYPES as $value => $typeLabel)
                <option value="{{ $value }}" @selected(old('field_type', $field?->field_type) === $value)>
                    {{ $typeLabel }}
                </option>
            @endforeach
        </select>
        @error('field_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="mb-3">
        <label class="form-label small fw-semibold">Label</label>
        <input type="text" name="label" class="form-control @error('label') is-invalid @enderror"
               value="{{ old('label', $field?->label) }}" required>
        @error('label') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Placeholder <span class="text-muted fw-normal">(opsional)</span></label>
            <input type="text" name="placeholder" class="form-control" value="{{ old('placeholder', $field?->placeholder) }}">
        </div>
        <div class="col-md-6 d-flex align-items-center">
            <div class="form-check mt-4">
                {{-- input hidden + checkbox: pola standar Laravel supaya checkbox yang
                     tidak dicentang tetap mengirim "0", bukan hilang dari request. --}}
                <input type="hidden" name="is_required" value="0">
                <input type="checkbox" name="is_required" value="1" class="form-check-input" id="required-{{ $uid }}"
                       @checked(old('is_required', $field?->is_required))>
                <label class="form-check-label small" for="required-{{ $uid }}">Wajib diisi</label>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label small fw-semibold">Teks Bantuan <span class="text-muted fw-normal">(opsional)</span></label>
        <textarea name="help_text" rows="2" class="form-control">{{ old('help_text', $field?->help_text) }}</textarea>
    </div>

    {{-- Ditampilkan/disembunyikan oleh script di builder.blade.php berdasarkan
         tipe field yang dipilih (select / radio / checkbox). Tidak dirender satu
         baris kosong default -- supaya field non-opsi (text, date, dst.) tidak
         ikut mengirim array "options" kosong yang bisa lolos validasi tak sengaja. --}}
    <div class="field-options-block border rounded p-3 bg-light" style="display: none;">
        <label class="form-label small fw-semibold d-block">Pilihan Opsi</label>
        <div id="options-container-{{ $uid }}" data-next-index="{{ $existingOptions->count() }}">
            @foreach ($existingOptions as $i => $option)
                <div class="row g-2 mb-2 align-items-center">
                    <div class="col-6">
                        <input type="text" name="options[{{ $i }}][label]" class="form-control form-control-sm" placeholder="Label opsi" value="{{ $option->label }}">
                    </div>
                    <div class="col-5">
                        <input type="text" name="options[{{ $i }}][value]" class="form-control form-control-sm" placeholder="Value (opsional)" value="{{ $option->value }}">
                    </div>
                    <div class="col-1">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-option-row-btn" title="Hapus opsi">&times;</button>
                    </div>
                </div>
            @endforeach
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary add-option-row-btn" data-target="options-container-{{ $uid }}">
            <i class="bi bi-plus-lg me-1"></i> Tambah Opsi
        </button>
        @error('options') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
    </div>

    {{-- Blok kolom Dynamic Table: ditampilkan/disembunyikan oleh script yang sama
         dengan blok Pilihan Opsi di atas, berdasarkan tipe field yang dipilih. --}}
    <div class="field-columns-block border rounded p-3 bg-light" style="display: none;">
        <label class="form-label small fw-semibold d-block">Kelola Kolom Tabel</label>
        <p class="text-muted small">
            Setiap baris tabel yang diisi User nanti akan punya kolom-kolom ini.
            Untuk kolom bertipe Select, isi pilihannya dipisahkan dengan koma (mis. "Basah, Kering, Beku").
        </p>
        <div id="columns-container-{{ $uid }}" data-next-index="{{ count($existingColumns) }}">
            @foreach ($existingColumns as $i => $column)
                <div class="row g-2 mb-2 align-items-start column-row">
                    <div class="col-4">
                        <input type="text" name="columns[{{ $i }}][label]" class="form-control form-control-sm"
                               placeholder="Label kolom" value="{{ $column['label'] }}">
                    </div>
                    <div class="col-3">
                        <select name="columns[{{ $i }}][type]" class="form-select form-select-sm column-type-select">
                            @foreach (\App\Models\FormField::TABLE_COLUMN_TYPES as $typeValue => $typeLabel)
                                <option value="{{ $typeValue }}" @selected(($column['type'] ?? 'text') === $typeValue)>
                                    {{ $typeLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-4">
                        <input type="text" name="columns[{{ $i }}][options_text]" class="form-control form-control-sm column-options-input"
                               placeholder="Pilihan (pisah koma)" value="{{ implode(', ', $column['options'] ?? []) }}"
                               style="{{ ($column['type'] ?? 'text') === 'select' ? '' : 'display:none;' }}">
                    </div>
                    <div class="col-1">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-column-row-btn" title="Hapus kolom">&times;</button>
                    </div>
                </div>
            @endforeach
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary add-column-row-btn" data-target="columns-container-{{ $uid }}">
            <i class="bi bi-plus-lg me-1"></i> Tambah Kolom
        </button>
        @error('columns') <div class="text-danger small mt-1">{{ $message }}</div> @enderror

        @if ($field?->isDynamicTable() && ! empty($field->tableDefaultRows()))
            <div class="alert alert-secondary small mt-3 mb-0">
                <i class="bi bi-info-circle me-1"></i>
                Field ini punya {{ count($field->tableDefaultRows()) }} baris standar (checklist tetap) yang dibuat lewat seeder.
                Baris standar belum bisa diedit lewat Builder di Phase 3 ini — hanya kolomnya.
            </div>
        @endif
    </div>
</div>
