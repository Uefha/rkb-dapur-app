<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Services\FormRendererService;
use App\Services\FormSubmissionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FormFillController extends Controller
{
    /**
     * Menampilkan form yang siap diisi, dirender penuh dari struktur
     * form_version yang sedang published (section, field, opsi, dynamic
     * table). Kalau User punya draft aktif untuk form ini, otomatis
     * melanjutkan draft tersebut (bukan mulai dari kosong) -- form
     * operasional ini diisi berulang kali, jadi hanya draft yang belum
     * selesai yang pernah di-resume; submission yang sudah submitted/
     * reviewed/approved/rejected tidak pernah mengganggu pengisian baru.
     */
    public function show(Request $request, Form $form, FormRendererService $renderer, FormSubmissionService $submissionService): View
    {
        $draft = $submissionService->findEditableSubmission($form, $request->user());
        $isRevision = $draft?->isRevisionRequested() ?? false;
        abort_unless($isRevision || $form->isAvailableTo($request->user()), 404);

        if ($isRevision) {
            $version = $draft->version->load([
                'sections' => fn ($query) => $query->orderBy('order'),
                'sections.fields' => fn ($query) => $query->orderBy('order'),
                'sections.fields.options' => fn ($query) => $query->orderBy('order'),
            ]);
        } else {
            $version = $renderer->renderableVersion($form);
        }

        $existingAnswers = $draft
            ? $draft->answers()->whereNull('form_submission_row_id')->get()->keyBy('form_field_id')
            : collect();

        $existingTableRows = $draft
            ? $draft->rows()->with('answers')->get()->groupBy('form_field_id')
            : collect();

        return view('user.forms.fill', compact('form', 'version', 'draft', 'existingAnswers', 'existingTableRows'));
    }
}
