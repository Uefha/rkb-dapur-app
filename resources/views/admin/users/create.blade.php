<x-layouts.app title="Tambah User - RKB Dapur" heading="Tambah User" subheading="Buat akun petugas baru">
    <div class="card border-0 shadow-sm"><div class="card-body"><form method="POST" action="{{ route('admin.users.store') }}">@csrf @include('admin.users.partials.form', ['creating' => true])</form></div></div>
</x-layouts.app>
