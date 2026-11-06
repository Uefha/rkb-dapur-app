<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Services\ActivityLogService;
use App\Services\FormRendererService;
use App\Services\FormSubmissionService;
use App\Services\FormValidationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FormSubmissionController extends Controller
{
    public function store(
        Request $request,
        Form $form,
        FormRendererService $renderer,
        FormValidationService $validationService,
        FormSubmissionService $submissionService,
        ActivityLogService $activityLog,
    ): RedirectResponse {
        $editable = $submissionService->findEditableSubmission($form, $request->user());
        $isRevision = $editable?->isRevisionRequested() ?? false;
        abort_unless($isRevision || $form->isAvailableTo($request->user()), 404);
        $intent = $request->input('intent') === 'submit' ? 'submit' : 'draft';

        $version = $isRevision
            ? $editable->version->load([
                'sections' => fn ($query) => $query->orderBy('order'),
                'sections.fields' => fn ($query) => $query->orderBy('order'),
                'sections.fields.options' => fn ($query) => $query->orderBy('order'),
            ])
            : $renderer->renderableVersion($form);

        // Melempar ValidationException kalau gagal -- Laravel otomatis
        // redirect back() dengan error + old input, sama seperti FormRequest.
        // Nilai KEMBALIAN validate() sengaja TIDAK dipakai untuk disimpan:
        // FormValidationService memang tidak membuat rule untuk field
        // dynamic_table (lihat catatan class-nya), dan Validator::validated()
        // di Laravel hanya menyertakan key yang benar-benar punya rule --
        // field tanpa rule (termasuk seluruh isi Dynamic Table) otomatis
        // hilang dari hasilnya, walau datanya ada di request. Kalau hasil
        // validate() ini yang dipakai untuk disimpan, isi tabel dinamis 8
        // dari 9 form akan senyap terbuang setiap kali submit/simpan draft.
        // Jadi validate() di sini murni untuk MENOLAK input yang salah;
        // yang benar-benar disimpan tetap $request->input('answers') mentah
        // -- FormSubmissionService::save() sudah punya lapisan keamanannya
        // sendiri (mencocokkan field_id ke field yang benar-benar ada di
        // versi form ini) jadi aman menerima input mentah di sini.
        $validationService->validate($version, $request->all(), $intent);

        $submission = $submissionService->saveSubmission($form, $version, $request->user(), $request->input('answers', []), $intent);

        $activityLog->log(
            $intent === 'submit' ? 'submission.submit' : 'submission.draft',
            ($intent === 'submit' ? 'Submit form' : 'Simpan draft form')." \"{$form->title}\"",
            $submission
        );

        return redirect()
            ->route('user.my-submissions.show', $submission)
            ->with('success', $intent === 'submit' ? 'Form berhasil dikirim.' : 'Draft berhasil disimpan.');
    }
}
