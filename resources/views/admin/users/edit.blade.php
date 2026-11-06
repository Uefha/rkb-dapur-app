<x-layouts.app title="Edit User - RKB Dapur" heading="Edit User" subheading="Perbarui data akun petugas">
    <div class="card border-0 shadow-sm"><div class="card-body"><form method="POST" action="{{ route('admin.users.update', $user) }}">@csrf @method('PUT') @include('admin.users.partials.form', ['creating' => false])</form></div></div>
</x-layouts.app>
