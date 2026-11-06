<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FormCatalogController extends Controller
{
    /**
     * Katalog "Form Tersedia" -- hanya form berstatus published yang tampil,
     * sesuai bagian 4 spesifikasi. Search (nama/kode), filter kategori, dan
     * sort mengikuti bagian 13.
     */
    public function index(Request $request): View
    {
        $query = Form::query()
            ->where('status', 'published')
            ->with('category');

        $query->where(function ($q) use ($request) {
            $q->where('visibility', 'all')->orWhereHas('assignedUsers', fn ($users) => $users->whereKey($request->user()->id));
        });

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($categoryId = $request->query('category')) {
            $query->where('category_id', $categoryId);
        }

        $sort = $request->query('sort', 'title_asc');
        match ($sort) {
            'title_desc' => $query->orderByDesc('title'),
            'newest' => $query->orderByDesc('created_at'),
            default => $query->orderBy('title'),
        };

        $forms = $query->paginate(9)->withQueryString();
        $categories = FormCategory::orderBy('name')->get();

        return view('user.forms.index', compact('forms', 'categories', 'sort'));
    }

    /**
     * Halaman detail: nama, kode, deskripsi, kategori, versi, status, tujuan,
     * petunjuk pengisian -- sebelum User memutuskan klik "Isi Form".
     */
    public function show(Form $form): View
    {
        abort_unless($form->status === 'published' && $form->isAvailableTo(request()->user()), 404);

        $form->load('category', 'currentVersion');

        return view('user.forms.show', compact('form'));
    }
}
