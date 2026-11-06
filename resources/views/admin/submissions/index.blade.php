<x-layouts.app title="Submission - RKB Dapur" heading="Submission" subheading="Tinjau dan verifikasi form yang sudah dikirim User">

    <form method="GET" class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3 p-lg-4">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-sm-6 col-xl-3">
                    <label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua (kecuali Draft)</option>
                        <option value="submitted" @selected(request('status') === 'submitted')>Menunggu Verifikasi</option>
                        <option value="reviewed" @selected(request('status') === 'reviewed')>Ditinjau</option>
                        <option value="approved" @selected(request('status') === 'approved')>Disetujui</option>
                        <option value="rejected" @selected(request('status') === 'rejected')>Ditolak / Perlu Revisi</option>
                        <option value="draft" @selected(request('status') === 'draft')>Draft (belum dikirim)</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <label class="form-label small mb-1">Form</label>
                    <select name="form_id" class="form-select form-select-sm">
                        <option value="">Semua Form</option>
                        @foreach ($forms as $form)
                            <option value="{{ $form->id }}" @selected(request('form_id') == $form->id)>{{ $form->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-xl-3"><label class="form-label small mb-1">Pengisi</label><select name="user_id" class="form-select form-select-sm"><option value="">Semua User</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>{{ $user->name }} — {{ $user->email }}</option>@endforeach</select></div>
                <div class="col-12 col-sm-6 col-xl-3"><label class="form-label small mb-1">Kategori</label><select name="category_id" class="form-select form-select-sm"><option value="">Semua Kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>@endforeach</select></div>
                <div class="col-12 col-sm-6 col-xl-3"><label class="form-label small mb-1">Tanggal dari</label><input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm"></div>
                <div class="col-12 col-sm-6 col-xl-3"><label class="form-label small mb-1">Tanggal sampai</label><input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm"></div>
                <div class="col-12 col-sm-6 col-xl-3"><label class="form-label small mb-1">Nomor urut</label><input name="submission_numbers" value="{{ request('submission_numbers') }}" class="form-control form-control-sm" placeholder="Mis. 1, 4, 7" inputmode="numeric"></div>
                <input type="hidden" name="sort_by" value="{{ request('sort_by', 'id') }}">
                <input type="hidden" name="sort_order" value="{{ request('sort_order', 'desc') }}">
                <div class="col-12 col-sm-6 col-xl-3"><label class="form-label small mb-1">Pencarian</label><input name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="ID, nama, form, atau email"></div>
                <div class="col-12 d-flex justify-content-end gap-2 pt-1"><button class="btn btn-brand btn-sm px-4"><i class="bi bi-funnel me-1"></i>Terapkan</button><a href="{{ route('admin.submissions.index') }}" class="btn btn-outline-secondary btn-sm px-3"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset</a></div>
            </div>
        </div>
    </form>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <span class="text-muted small"><strong class="text-dark">{{ $submissions->total() }}</strong> submission cocok dengan filter</span>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <label for="export-orientation" class="small text-muted">Orientasi dokumen</label>
            <select id="export-orientation" class="form-select form-select-sm" style="width:auto" onchange="document.querySelectorAll('[data-export-format]').forEach(link => { const url = new URL(link.href); url.searchParams.set('orientation', this.value); link.href = url.toString(); })">
                <option value="portrait" @selected(request('orientation', 'landscape') === 'portrait')>Potret</option>
                <option value="landscape" @selected(request('orientation', 'landscape') === 'landscape')>Landscape</option>
            </select>
            <div class="btn-group btn-group-sm" role="group" aria-label="Ekspor submission">
                <a data-export-format class="btn btn-outline-success" href="{{ route('admin.submissions.export', array_merge(['format' => 'excel'], request()->query())) }}"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Excel</a>
                <a data-export-format class="btn btn-outline-primary" href="{{ route('admin.submissions.export', array_merge(['format' => 'word'], request()->query())) }}"><i class="bi bi-file-earmark-word me-1"></i>Word</a>
                <a data-export-format class="btn btn-outline-danger" href="{{ route('admin.submissions.export', array_merge(['format' => 'pdf'], request()->query())) }}"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>
            </div>
        </div>
    </div>

    @if ($submissions->isEmpty())
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-5 text-center text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                Belum ada submission untuk filter ini.
            </div>
        </div>
    @else
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            @php
                                $sortColumnUrl = fn ($column) => route('admin.submissions.index', array_merge(request()->except('page'), [
                                    'sort_by' => $column,
                                    'sort_order' => request('sort_by') === $column && request('sort_order', 'desc') === 'asc' ? 'desc' : 'asc',
                                ]));
                                $sortDirection = fn ($column) => request('sort_by', 'id') === $column ? request('sort_order', 'desc') : null;
                            @endphp
                            <th class="text-nowrap"><a class="text-decoration-none text-reset" href="{{ $sortColumnUrl('id') }}">No. <i class="bi {{ $sortDirection('id') === 'asc' ? 'bi-arrow-up' : ($sortDirection('id') === 'desc' ? 'bi-arrow-down' : 'bi-arrow-down-up') }}"></i></a></th>
                            <th>Form</th>
                            <th>Pengisi</th>
                            <th class="text-nowrap"><a class="text-decoration-none text-reset" href="{{ $sortColumnUrl('date') }}">Tanggal <i class="bi {{ $sortDirection('date') === 'asc' ? 'bi-arrow-up' : ($sortDirection('date') === 'desc' ? 'bi-arrow-down' : 'bi-arrow-down-up') }}"></i></a></th>
                            <th class="text-center text-nowrap">Versi</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($submissions as $submission)
                            <tr>
                                <td class="small fw-semibold">{{ $submission->daily_sequence }}</td>
                                <td>
                                    {{ $submission->form->title }}
                                    <span class="d-block text-muted small">{{ $submission->form->category->name }}</span>
                                </td>
                                <td class="small">{{ $submission->user->name }}</td>
                                <td class="text-muted small text-nowrap">{{ ($submission->submitted_at ?? $submission->created_at)->format('d M Y H:i') }}</td>
                                <td class="text-center text-muted small">v{{ $submission->form_version_id ? $submission->version->version_number : '-' }}</td>
                                <td class="text-center">
                                    <span class="badge {{ match ($submission->status) {
                                        'submitted' => 'bg-warning text-dark',
                                        'reviewed' => 'bg-info text-dark',
                                        'approved' => 'bg-success',
                                        'rejected' => $submission->isRevisionRequested() ? 'bg-primary' : 'bg-danger',
                                        'draft' => 'bg-secondary',
                                        default => 'bg-secondary',
                                    } }}">
                                        {{ $submission->statusLabel() }}
                                    </span>
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('admin.submissions.show', array_merge(['submission' => $submission], request()->query())) }}" class="btn btn-sm btn-outline-secondary">
                                        Detail
                                    </a>
                                    <form class="d-inline" method="POST" action="{{ route('admin.submissions.destroy', array_merge(['submission' => $submission], request()->query())) }}"
                                          data-confirm-title="Hapus submission?" data-confirm-tone="danger" data-confirm-button="Hapus permanen"
                                          data-confirm-message="Submission #{{ $submission->id }} beserta semua jawabannya akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" title="Hapus submission"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $submissions->links() }}
        </div>
    @endif

</x-layouts.app>
