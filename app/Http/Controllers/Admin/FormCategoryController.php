<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFormCategoryRequest;
use App\Http\Requests\Admin\UpdateFormCategoryRequest;
use App\Models\FormCategory;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FormCategoryController extends Controller
{
    public function index(): View
    {
        $categories = FormCategory::withCount('forms')->orderBy('name')->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.categories.create');
    }

    public function store(StoreFormCategoryRequest $request, ActivityLogService $activityLog): RedirectResponse
    {
        $category = FormCategory::create([
            'name' => $request->name,
            'slug' => FormCategory::generateUniqueSlug($request->name),
            'description' => $request->description,
            'created_by' => $request->user()->id,
        ]);

        $activityLog->log('category.create', "Membuat kategori form \"{$category->name}\"", $category);

        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil dibuat.');
    }

    public function edit(FormCategory $category): View
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(UpdateFormCategoryRequest $request, FormCategory $category, ActivityLogService $activityLog): RedirectResponse
    {
        $category->update([
            'name' => $request->name,
            'slug' => $category->name === $request->name
                ? $category->slug
                : FormCategory::generateUniqueSlug($request->name, $category->id),
            'description' => $request->description,
        ]);

        $activityLog->log('category.update', "Memperbarui kategori form \"{$category->name}\"", $category);

        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(FormCategory $category, ActivityLogService $activityLog): RedirectResponse
    {
        // withTrashed() sengaja dipakai di sini, bukan exists() polos. Form yang
        // sudah di-soft-delete tetap punya baris nyata di tabel `forms` dengan
        // category_id yang masih menunjuk ke kategori ini, dan foreign key
        // constraint di database tidak peduli status soft-delete sama sekali --
        // jadi walau form itu sudah "hilang" dari daftar Manajemen Form, DELETE
        // kategori tetap akan ditolak MySQL. Mengecek tanpa withTrashed() bikin
        // guard ini salah memprediksi aman dihapus padahal sebenarnya tidak.
        if ($category->forms()->withTrashed()->exists()) {
            return back()->with('error', 'Kategori tidak bisa dihapus karena masih terhubung dengan satu atau lebih form (termasuk form yang sudah dihapus).');
        }

        $name = $category->name;
        $category->delete();

        $activityLog->log('category.delete', "Menghapus kategori form \"{$name}\"");

        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil dihapus.');
    }
}
