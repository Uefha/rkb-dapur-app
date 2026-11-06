<div class="mb-3"><label class="form-label">Nama</label><input class="form-control" name="name" value="{{ old('name', $user->name) }}" required maxlength="255"></div>
<div class="mb-3"><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="{{ old('email', $user->email) }}" required></div>
<div class="mb-3"><label class="form-label">{{ $creating ? 'Kata sandi' : 'Kata sandi baru (opsional)' }}</label><input class="form-control" type="password" name="password" {{ $creating ? 'required' : '' }} minlength="8" autocomplete="new-password"></div>
<div class="mb-3"><label class="form-label">Konfirmasi kata sandi</label><input class="form-control" type="password" name="password_confirmation" {{ $creating ? 'required' : '' }} minlength="8" autocomplete="new-password"></div>
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Batal</a> <button class="btn btn-brand">Simpan</button>
