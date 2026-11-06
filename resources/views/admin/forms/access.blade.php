<x-layouts.app title="Akses Form - RKB Dapur" heading="Atur Akses Form" subheading="{{ $form->title }}">
    <div class="card border-0 shadow-sm"><div class="card-body"><form method="POST" action="{{ route('admin.forms.access.update', $form) }}">@csrf @method('PUT')
        @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
        <fieldset class="mb-4"><legend class="fs-6 fw-semibold">Form ditampilkan kepada</legend>
            <div class="form-check"><input class="form-check-input" type="radio" name="visibility" id="visibility-all" value="all" @checked(old('visibility', $form->visibility) === 'all')><label class="form-check-label" for="visibility-all">Semua user aktif</label></div>
            <div class="form-check"><input class="form-check-input" type="radio" name="visibility" id="visibility-selected" value="selected" @checked(old('visibility', $form->visibility) === 'selected')><label class="form-check-label" for="visibility-selected">User tertentu saja</label></div>
        </fieldset>
        <div class="mb-4"><label class="form-label fw-semibold">Pilih user yang mendapat akses</label><div class="row g-2">@forelse($users as $user)<div class="col-md-6"><label class="form-check border rounded p-2 ps-5"><input class="form-check-input" type="checkbox" name="user_ids[]" value="{{ $user->id }}" @checked(in_array($user->id, old('user_ids', $assignedUserIds)))> {{ $user->name }} <span class="text-muted small">({{ $user->email }})</span></label></div>@empty<p class="text-muted">Belum ada user. Buat akun melalui Manajemen User terlebih dahulu.</p>@endforelse</div><div class="form-text">Pilihan ini berlaku saat cakupan “User tertentu saja” dipilih.</div></div>
        <a href="{{ route('admin.forms.index') }}" class="btn btn-outline-secondary">Kembali</a> <button class="btn btn-brand">Simpan Akses</button>
    </form></div></div>
</x-layouts.app>
