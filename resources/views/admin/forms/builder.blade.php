<x-layouts.app title="Form Builder - {{ $form->title }}" heading="Form Builder" subheading="{{ $form->code }} — {{ $form->title }}">

    @php($locked = $version->status === 'published')

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <span class="badge {{ $locked ? 'bg-success' : 'bg-secondary' }}">
                    v{{ $version->version_number }} · {{ $locked ? 'Published (Terkunci)' : 'Draft (Bisa Diedit)' }}
                </span>
                <span class="badge bg-light text-dark border">{{ $form->category->name }}</span>
                <div class="text-muted small mt-1">
                    Status katalog:
                    <strong>{{ $form->statusLabel() }}</strong>
                </div>
            </div>
            <div>
                @if ($locked)
                    <form action="{{ route('admin.forms.new-version', $form) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-success btn-sm">
                            <i class="bi bi-file-earmark-plus me-1"></i> Buat Versi Baru untuk Edit
                        </button>
                    </form>
                @else
                    <form action="{{ route('admin.forms.publish', $form) }}" method="POST" class="d-inline"
                          data-confirm-title="Publish form?" data-confirm-message="Struktur versi {{ $version->version_number }} akan terkunci dan form ditampilkan pada katalog User." data-confirm-tone="success" data-confirm-button="Ya, publish">
                        @csrf
                        <button type="submit" class="btn btn-brand btn-sm">
                            <i class="bi bi-check2-circle me-1"></i> Publish Form
                        </button>
                    </form>
                @endif
                <a href="{{ route('admin.forms.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
            </div>
        </div>
    </div>

    @if ($locked)
        <div class="alert alert-info border-0 shadow-sm">
            Versi ini sudah dipublish sehingga strukturnya terkunci (menjaga histori submission yang memakainya).
            Klik <strong>"Buat Versi Baru untuk Edit"</strong> di atas untuk melanjutkan penyusunan form.
        </div>
    @endif

    @forelse ($version->sections as $section)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-start">
                <div>
                    <strong>{{ $section->title }}</strong>
                    @if ($section->description)
                        <div class="text-muted small">{{ $section->description }}</div>
                    @endif
                </div>
                @unless ($locked)
                    <div class="btn-group btn-group-sm">
                        <form action="{{ route('admin.forms.builder.sections.move', [$form, $section, 'up']) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary" title="Naikkan urutan"><i class="bi bi-arrow-up"></i></button>
                        </form>
                        <form action="{{ route('admin.forms.builder.sections.move', [$form, $section, 'down']) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary" title="Turunkan urutan"><i class="bi bi-arrow-down"></i></button>
                        </form>
                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editSectionModal{{ $section->id }}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <form action="{{ route('admin.forms.builder.sections.destroy', [$form, $section]) }}" method="POST"
                              data-confirm-title="Hapus section?" data-confirm-message="Section {{ $section->title }} dan seluruh field di dalamnya akan dihapus." data-confirm-tone="danger" data-confirm-button="Ya, hapus section">
                            @csrf
                            @method('delete')
                            <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                @endunless
            </div>
            <div class="card-body">
                @forelse ($section->fields as $field)
                    <div class="d-flex justify-content-between align-items-start py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div>
                            <span class="badge bg-light text-dark border">{{ $field->typeLabel() }}</span>
                            <span class="fw-semibold">{{ $field->label }}</span>
                            @if ($field->is_required)
                                <span class="text-danger" title="Wajib diisi">*</span>
                            @endif
                            @if ($field->hasOptions() && $field->options->isNotEmpty())
                                <div class="text-muted small">Opsi: {{ $field->options->pluck('label')->implode(', ') }}</div>
                            @endif
                            @if ($field->isDynamicTable())
                                <div class="text-muted small">
                                    Kolom: {{ collect($field->tableColumns())->pluck('label')->implode(', ') ?: '(belum ada kolom)' }}
                                </div>
                                @if (! empty($field->tableDefaultRows()))
                                    <div class="text-muted small fst-italic">{{ count($field->tableDefaultRows()) }} baris standar (checklist tetap)</div>
                                @endif
                            @endif
                            @if ($field->help_text)
                                <div class="text-muted small fst-italic">{{ $field->help_text }}</div>
                            @endif
                        </div>
                        @unless ($locked)
                            <div class="btn-group btn-group-sm flex-shrink-0">
                                <form action="{{ route('admin.forms.builder.fields.move', [$form, $field, 'up']) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-arrow-up"></i></button>
                                </form>
                                <form action="{{ route('admin.forms.builder.fields.move', [$form, $field, 'down']) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-arrow-down"></i></button>
                                </form>
                                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editFieldModal{{ $field->id }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                    <form action="{{ route('admin.forms.builder.fields.destroy', [$form, $field]) }}" method="POST"
                                          data-confirm-title="Hapus field?" data-confirm-message="Field {{ $field->label }} akan dihapus." data-confirm-tone="danger" data-confirm-button="Ya, hapus field">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        @endunless
                    </div>
                @empty
                    <p class="text-muted small mb-0">Belum ada field di section ini.</p>
                @endforelse

                @unless ($locked)
                    <button type="button" class="btn btn-sm btn-outline-success mt-3" data-bs-toggle="modal" data-bs-target="#addFieldModal{{ $section->id }}">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Field
                    </button>
                @endunless
            </div>
        </div>
    @empty
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center text-muted py-5">
                Belum ada section. Tambahkan section pertama untuk mulai menyusun form.
            </div>
        </div>
    @endforelse

    @unless ($locked)
        <button type="button" class="btn btn-brand btn-sm" data-bs-toggle="modal" data-bs-target="#addSectionModal">
            <i class="bi bi-plus-lg me-1"></i> Tambah Section
        </button>
    @endunless

    {{-- ============ MODALS (di luar loop utama, id unik per section/field) ============ --}}

    <div class="modal fade" id="addSectionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.forms.builder.sections.store', $form) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Section</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Judul Section</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="mb-0">
                            <label class="form-label small fw-semibold">Deskripsi <span class="text-muted fw-normal">(opsional)</span></label>
                            <textarea name="description" rows="2" class="form-control"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-brand">Simpan Section</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @foreach ($version->sections as $section)
        <div class="modal fade" id="editSectionModal{{ $section->id }}" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('admin.forms.builder.sections.update', [$form, $section]) }}" method="POST">
                        @csrf
                        @method('put')
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Section</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Judul Section</label>
                                <input type="text" name="title" class="form-control" value="{{ $section->title }}" required>
                            </div>
                            <div class="mb-0">
                                <label class="form-label small fw-semibold">Deskripsi <span class="text-muted fw-normal">(opsional)</span></label>
                                <textarea name="description" rows="2" class="form-control">{{ $section->description }}</textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-brand">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="addFieldModal{{ $section->id }}" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form action="{{ route('admin.forms.builder.fields.store', [$form, $section]) }}" method="POST">
                        @csrf
                        @include('admin.forms.partials.field-form-body', ['field' => null])
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-brand">Simpan Field</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @foreach ($section->fields as $field)
            <div class="modal fade" id="editFieldModal{{ $field->id }}" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form action="{{ route('admin.forms.builder.fields.update', [$form, $field]) }}" method="POST">
                            @csrf
                            @method('put')
                            @include('admin.forms.partials.field-form-body', ['field' => $field])
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-brand">Simpan Perubahan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    @endforeach

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var optionTypes = ['select', 'radio', 'checkbox'];
            var tableColumnTypes = {
                text: 'Text', number: 'Number', date: 'Date', time: 'Time', select: 'Select (Dropdown)'
            };

            function toggleOptionsVisibility(select) {
                var form = select.closest('form');
                var optionsBlock = form.querySelector('.field-options-block');
                if (!optionsBlock) return;
                optionsBlock.style.display = optionTypes.indexOf(select.value) !== -1 ? '' : 'none';
            }

            function toggleColumnsVisibility(select) {
                var form = select.closest('form');
                var columnsBlock = form.querySelector('.field-columns-block');
                if (!columnsBlock) return;
                columnsBlock.style.display = select.value === 'dynamic_table' ? '' : 'none';
            }

            document.querySelectorAll('.field-type-select').forEach(function (select) {
                toggleOptionsVisibility(select);
                toggleColumnsVisibility(select);
                select.addEventListener('change', function () {
                    toggleOptionsVisibility(select);
                    toggleColumnsVisibility(select);
                });
            });

            document.querySelectorAll('.add-option-row-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var container = document.getElementById(btn.dataset.target);
                    // Counter monoton, bukan container.children.length -- supaya index
                    // baris baru tidak pernah tabrakan dengan baris yang masih ada
                    // setelah ada baris lain yang dihapus (lihat remove-option-row-btn).
                    var index = parseInt(container.dataset.nextIndex || '0', 10);
                    container.dataset.nextIndex = index + 1;
                    var row = document.createElement('div');
                    row.className = 'row g-2 mb-2 align-items-center';
                    row.innerHTML =
                        '<div class="col-6"><input type="text" name="options[' + index + '][label]" class="form-control form-control-sm" placeholder="Label opsi"></div>' +
                        '<div class="col-5"><input type="text" name="options[' + index + '][value]" class="form-control form-control-sm" placeholder="Value (opsional)"></div>' +
                        '<div class="col-1"><button type="button" class="btn btn-sm btn-outline-danger remove-option-row-btn" title="Hapus opsi">&times;</button></div>';
                    container.appendChild(row);
                });
            });

            document.querySelectorAll('.add-column-row-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var container = document.getElementById(btn.dataset.target);
                    var index = parseInt(container.dataset.nextIndex || '0', 10);
                    container.dataset.nextIndex = index + 1;

                    var typeOptions = '';
                    Object.keys(tableColumnTypes).forEach(function (value) {
                        typeOptions += '<option value="' + value + '">' + tableColumnTypes[value] + '</option>';
                    });

                    var row = document.createElement('div');
                    row.className = 'row g-2 mb-2 align-items-start column-row';
                    row.innerHTML =
                        '<div class="col-4"><input type="text" name="columns[' + index + '][label]" class="form-control form-control-sm" placeholder="Label kolom"></div>' +
                        '<div class="col-3"><select name="columns[' + index + '][type]" class="form-select form-select-sm column-type-select">' + typeOptions + '</select></div>' +
                        '<div class="col-4"><input type="text" name="columns[' + index + '][options_text]" class="form-control form-control-sm column-options-input" placeholder="Pilihan (pisah koma)" style="display:none;"></div>' +
                        '<div class="col-1"><button type="button" class="btn btn-sm btn-outline-danger remove-column-row-btn" title="Hapus kolom">&times;</button></div>';
                    container.appendChild(row);
                });
            });

            // Event delegation: menangani tombol hapus baris opsi/kolom, dan toggle
            // input "Pilihan (pisah koma)" per baris kolom -- berlaku baik untuk baris
            // yang dirender server (Blade) maupun yang ditambahkan lewat JS di atas.
            document.addEventListener('click', function (e) {
                var removeOptionBtn = e.target.closest('.remove-option-row-btn');
                if (removeOptionBtn) {
                    removeOptionBtn.closest('.row').remove();
                    return;
                }

                var removeColumnBtn = e.target.closest('.remove-column-row-btn');
                if (removeColumnBtn) {
                    removeColumnBtn.closest('.column-row').remove();
                }
            });

            document.addEventListener('change', function (e) {
                if (e.target.matches('.column-type-select')) {
                    var row = e.target.closest('.column-row');
                    var optionsInput = row ? row.querySelector('.column-options-input') : null;
                    if (optionsInput) {
                        optionsInput.style.display = e.target.value === 'select' ? '' : 'none';
                    }
                }
            });
        });
    </script>

</x-layouts.app>
