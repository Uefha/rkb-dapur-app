<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\FormSubmission;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MySubmissionController extends Controller
{
    public function index(Request $request): View
    {
        $submissions = FormSubmission::query()
            ->where('user_id', $request->user()->id)
            ->with('form.category')
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('user.submissions.index', compact('submissions'));
    }

    public function show(Request $request, FormSubmission $submission): View
    {
        // Ownership check: User hanya boleh melihat submission miliknya
        // sendiri (bagian 4 spesifikasi -- "User TIDAK dapat melihat
        // submission User lain"), terlepas dari apakah dia tahu ID-nya.
        abort_unless($submission->user_id === $request->user()->id, 404);

        $submission->load([
            'form.category',
            'version.sections.fields.options',
            'reviewer',
            'answers',
            'rows.answers',
        ]);

        // Dikelompokkan di sini (bukan di view) supaya konsisten dengan pola
        // yang sama persis dipakai FormFillController untuk melanjutkan
        // draft -- satu cara membaca jawaban tersimpan untuk seluruh aplikasi.
        $answersByField = $submission->answers->whereNull('form_submission_row_id')->keyBy('form_field_id');
        $rowsByField = $submission->rows->groupBy('form_field_id');

        return view('user.submissions.show', compact('submission', 'answersByField', 'rowsByField'));
    }

    /**
     * Hapus draft milik sendiri. Hanya status draft yang boleh dihapus --
     * submission yang sudah submitted/reviewed/approved/rejected adalah
     * catatan resmi yang tidak boleh dihapus User (kalau butuh dibatalkan,
     * itu wewenang Admin lewat alur review, bukan dihapus begitu saja).
     */
    public function destroy(Request $request, FormSubmission $submission, ActivityLogService $activityLog): RedirectResponse
    {
        abort_unless($submission->user_id === $request->user()->id, 404);
        abort_unless($submission->isDraft(), 403, 'Hanya draft yang bisa dihapus.');

        $formTitle = $submission->form->title;
        $submission->delete();

        $activityLog->log('submission.delete_draft', "Menghapus draft form \"{$formTitle}\"");

        return redirect()->route('user.my-submissions.index', $request->only('status'))
            ->with('success', 'Draft berhasil dihapus.');
    }
}
