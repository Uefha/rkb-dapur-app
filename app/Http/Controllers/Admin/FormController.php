<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DuplicateFormRequest;
use App\Http\Requests\Admin\StoreFormRequest;
use App\Http\Requests\Admin\UpdateFormRequest;
use App\Models\Form;
use App\Models\FormCategory;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\DynamicFormService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FormController extends Controller
{
    public function index(): View
    {
        $forms = Form::with(['category', 'versions' => fn ($q) => $q->limit(1)])
            ->orderByDesc('updated_at')
            ->get();

        return view('admin.forms.index', compact('forms'));
    }

    public function create(): View
    {
        $categories = FormCategory::orderBy('name')->get();

        return view('admin.forms.create', compact('categories'));
    }

    public function store(StoreFormRequest $request, DynamicFormService $formService, ActivityLogService $activityLog): RedirectResponse
    {
        $form = $formService->createForm($request->validated(), $request->user());

        $activityLog->log('form.create', "Membuat form \"{$form->title}\" ({$form->code})", $form);

        return redirect()->route('admin.forms.builder.index', $form)
            ->with('success', 'Form berhasil dibuat. Silakan tambahkan section & field.');
    }

    public function edit(Form $form): View
    {
        $categories = FormCategory::orderBy('name')->get();

        return view('admin.forms.edit', compact('form', 'categories'));
    }

    public function access(Form $form): View
    {
        $users = User::whereHas('role', fn ($q) => $q->where('name', Role::USER))->orderBy('name')->get();
        $assignedUserIds = $form->assignedUsers()->pluck('users.id')->all();

        return view('admin.forms.access', compact('form', 'users', 'assignedUserIds'));
    }

    public function updateAccess(Request $request, Form $form, ActivityLogService $activityLog): RedirectResponse
    {
        $data = $request->validate([
            'visibility' => ['required', 'in:all,selected'],
            'user_ids' => ['array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ], [
            'visibility.required' => 'Pilih cakupan pengguna.',
            'user_ids.*.exists' => 'Salah satu pengguna tidak valid.',
        ]);

        $userIds = User::whereHas('role', fn ($q) => $q->where('name', Role::USER))
            ->whereIn('id', $data['user_ids'] ?? [])->pluck('id');
        if ($data['visibility'] === 'selected' && $userIds->isEmpty()) {
            return back()->withInput()->withErrors(['user_ids' => 'Pilih minimal satu pengguna untuk cakupan khusus.']);
        }

        $form->update(['visibility' => $data['visibility']]);
        $form->assignedUsers()->sync($data['visibility'] === 'selected' ? $userIds : []);
        $activityLog->log('form.access.update', "Mengatur akses form \"{$form->title}\"", $form, ['visibility' => $data['visibility'], 'user_ids' => $userIds->all()]);

        return redirect()->route('admin.forms.index')->with('success', 'Akses form berhasil diperbarui.');
    }

    public function update(UpdateFormRequest $request, Form $form, DynamicFormService $formService, ActivityLogService $activityLog): RedirectResponse
    {
        $formService->updateForm($form, $request->validated());

        $activityLog->log('form.update', "Memperbarui metadata form \"{$form->title}\"", $form);

        return redirect()->route('admin.forms.index')->with('success', 'Form berhasil diperbarui.');
    }

    public function destroy(Form $form, ActivityLogService $activityLog): RedirectResponse
    {
        $title = $form->title;

        // Bebaskan "code" sebelum soft-delete: unique index di level database
        // tidak tahu soal deleted_at (beda dengan aturan unique di Form Request,
        // yang sudah difilter whereNull('deleted_at')). Tanpa ini, kode dokumen
        // dari form yang dihapus tetap terkunci permanen dan INSERT berikutnya
        // dengan kode yang sama akan gagal dengan duplicate-key QueryException
        // mentah -- bukan pesan validasi yang rapi. Pola yang sama seperti
        // penanganan email saat soft-delete user di project sebelumnya.
        $form->update(['code' => $form->code.'__deleted_'.$form->id]);
        $form->delete();

        $activityLog->log('form.delete', "Menghapus form \"{$title}\"");

        return redirect()->route('admin.forms.index')->with('success', 'Form berhasil dihapus.');
    }

    public function publish(Form $form, DynamicFormService $formService, ActivityLogService $activityLog): RedirectResponse
    {
        try {
            $formService->publishForm($form);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $activityLog->log('form.publish', "Publish form \"{$form->title}\"", $form);

        return back()->with('success', 'Form berhasil dipublish dan tampil di katalog User.');
    }

    public function unpublish(Form $form, DynamicFormService $formService, ActivityLogService $activityLog): RedirectResponse
    {
        $formService->unpublishForm($form);

        $activityLog->log('form.unpublish', "Unpublish form \"{$form->title}\"", $form);

        return back()->with('success', 'Form disembunyikan dari katalog User.');
    }

    public function archive(Form $form, DynamicFormService $formService, ActivityLogService $activityLog): RedirectResponse
    {
        $formService->archiveForm($form);

        $activityLog->log('form.archive', "Archive form \"{$form->title}\"", $form);

        return back()->with('success', 'Form berhasil diarsipkan.');
    }

    public function newVersion(Request $request, Form $form, DynamicFormService $formService, ActivityLogService $activityLog): RedirectResponse
    {
        try {
            $version = $formService->createNewVersion($form, $request->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $activityLog->log('form.new_version', "Membuat versi baru (v{$version->version_number}) untuk form \"{$form->title}\"", $form);

        return redirect()->route('admin.forms.builder.index', $form)
            ->with('success', "Versi baru (v{$version->version_number}) siap diedit.");
    }

    public function duplicate(DuplicateFormRequest $request, Form $form, DynamicFormService $formService, ActivityLogService $activityLog): RedirectResponse
    {
        $newForm = $formService->duplicateForm($form, $request->validated(), $request->user());

        $activityLog->log('form.duplicate', "Duplicate form \"{$form->title}\" menjadi \"{$newForm->title}\"", $newForm);

        return redirect()->route('admin.forms.builder.index', $newForm)
            ->with('success', 'Form berhasil diduplikasi.');
    }
}
