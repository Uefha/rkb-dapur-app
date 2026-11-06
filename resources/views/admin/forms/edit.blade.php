<x-layouts.app title="Edit Form - RKB Dapur" heading="Edit Metadata Form" subheading="{{ $form->code }} — {{ $form->title }}">

    <div class="card border-0 shadow-sm" style="max-width: 760px;">
        <div class="card-body p-4">
            <form action="{{ route('admin.forms.update', $form) }}" method="POST">
                @csrf
                @method('put')
                @include('admin.forms.partials.form-fields', ['categories' => $categories, 'form' => $form])

                <a href="{{ route('admin.forms.index') }}" class="btn btn-outline-secondary">Batal</a>
                <button type="submit" class="btn btn-brand">Simpan Perubahan</button>
            </form>
        </div>
    </div>

</x-layouts.app>
