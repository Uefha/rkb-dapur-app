@php($form = $form ?? null)

<div class="mb-3">
    <label class="form-label small fw-semibold">Kategori</label>
    <select name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
        <option value="">— Pilih Kategori —</option>
        @foreach ($categories as $category)
            <option value="{{ $category->id }}" @selected(old('category_id', $form?->category_id) == $category->id)>
                {{ $category->name }}
            </option>
        @endforeach
    </select>
    @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <label class="form-label small fw-semibold">Kode Dokumen</label>
        <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
               value="{{ old('code', $form?->code) }}" placeholder="cth. FRM-DU/01" required>
        @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-8">
        <label class="form-label small fw-semibold">Judul Form</label>
        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
               value="{{ old('title', $form?->title) }}" required>
        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="mb-3">
    <label class="form-label small fw-semibold">Deskripsi Singkat <span class="text-muted fw-normal">(opsional)</span></label>
    <textarea name="description" rows="2" class="form-control @error('description') is-invalid @enderror">{{ old('description', $form?->description) }}</textarea>
    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label class="form-label small fw-semibold">Tujuan <span class="text-muted fw-normal">(opsional)</span></label>
    <textarea name="purpose" rows="2" class="form-control @error('purpose') is-invalid @enderror">{{ old('purpose', $form?->purpose) }}</textarea>
    @error('purpose') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-4">
    <label class="form-label small fw-semibold">Petunjuk Pengisian <span class="text-muted fw-normal">(opsional)</span></label>
    <textarea name="instructions" rows="3" class="form-control @error('instructions') is-invalid @enderror">{{ old('instructions', $form?->instructions) }}</textarea>
    @error('instructions') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
