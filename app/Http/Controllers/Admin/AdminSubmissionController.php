<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\SubmissionExport;
use App\Models\Form;
use App\Models\FormCategory;
use App\Models\FormSubmission;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\SubmissionExportService;
use App\Services\SubmissionReviewService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminSubmissionController extends Controller
{
    /**
     * Draft User TIDAK ikut tampil di sini secara default -- draft adalah
     * pekerjaan yang belum selesai/belum dikirim, bukan sesuatu yang perlu
     * ditinjau Admin. Filter status boleh diarahkan ke draft secara eksplisit
     * kalau memang dibutuhkan (mis. debugging), tapi bukan tampilan awal.
     */
    public function index(Request $request): View
    {
        $this->validateFilters($request);
        $submissions = app(SubmissionExportService::class)->query($request)->paginate(15)->withQueryString();
        $forms = Form::orderBy('title')->get(['id', 'title']);
        $categories = FormCategory::orderBy('name')->get(['id', 'name']);
        $users = User::whereHas('role', fn ($q) => $q->where('name', Role::USER))->orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.submissions.index', compact('submissions', 'forms', 'categories', 'users'));
    }

    public function export(Request $request, string $format, SubmissionExportService $exporter)
    {
        abort_unless(in_array($format, ['excel', 'word', 'pdf'], true), 404);
        $this->validateFilters($request);
        $orientation = $request->query('orientation', 'landscape');
        $submissions = $exporter->query($request)->get();
        $filename = 'submission-rkb-dapur-'.now()->format('Ymd-His');

        if ($format === 'excel') {
            [$headings, $rows] = $exporter->spreadsheet($submissions);
            return Excel::download(new SubmissionExport($headings, $rows, $orientation), $filename.'.xlsx');
        }

        if ($format === 'pdf') {
            return Pdf::loadView('admin.submissions.export-document', compact('submissions', 'exporter'))
                ->setPaper('a4', $orientation)->download($filename.'.pdf');
        }

        $word = new \PhpOffice\PhpWord\PhpWord();
        $word->setDefaultFontName('Arial');
        $word->setDefaultFontSize(9);
        $section = $word->addSection(['orientation' => $orientation, 'marginLeft' => 700, 'marginRight' => 700]);
        $section->addTitle('Laporan Submission RKB Dapur', 1);
        $section->addText('Dibuat: '.now()->format('d-m-Y H:i').' WITA');
        if ($submissions->isEmpty()) $section->addText('Tidak ada submission sesuai filter.');
        foreach ($submissions as $index => $submission) {
            if ($index > 0) $section->addPageBreak();
            $section->addTitle($submission->form?->title.' — #'.$submission->id, 2);
            $section->addText('Pengisi: '.($submission->user?->name ?? '-').' ('.($submission->user?->email ?? '-').')');
            $section->addText('Kategori: '.($submission->form?->category?->name ?? '-').' | Status: '.$submission->statusLabel().' | Dikirim: '.($submission->submitted_at ? $submission->submitted_at->format('d-m-Y H:i').' WITA' : '-'));
            $section->addText('Versi: '.($submission->version?->version_number ?? '-').' | Ditinjau oleh: '.($submission->reviewer?->name ?? '-'));
            if ($submission->review_notes) $section->addText('Catatan review: '.$submission->review_notes);
            foreach ($exporter->details($submission) as $detail) {
                $section->addText($detail['label'], ['bold' => true]);
                if ($detail['table']) {
                    if (empty($detail['rows'])) { $section->addText('Tidak ada data.'); continue; }
                    $table = $section->addTable(['borderSize' => 4, 'borderColor' => 'B7C9C0', 'cellMargin' => 60]);
                    $table->addRow();
                    foreach ($detail['columns'] as $column) $table->addCell(2200)->addText($column['label'], ['bold' => true]);
                    foreach ($detail['rows'] as $row) {
                        $table->addRow();
                        foreach ($detail['columns'] as $column) $table->addCell(2200)->addText((string) ($row[$column['label']] ?? ''));
                    }
                } else {
                    $section->addText($detail['value'] !== '' ? $detail['value'] : '—');
                }
            }
        }

        $temporaryFile = tempnam(sys_get_temp_dir(), 'rkb-word-');
        $previousEscaping = \PhpOffice\PhpWord\Settings::isOutputEscapingEnabled();

        try {
            // PHPWord menulis teks mentah secara default. Label/data yang
            // berisi &, <, atau > dapat merusak XML di dalam arsip DOCX.
            \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);
            \PhpOffice\PhpWord\IOFactory::createWriter($word, 'Word2007')->save($temporaryFile);
        } catch (\Throwable $exception) {
            @unlink($temporaryFile);
            throw $exception;
        } finally {
            \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled($previousEscaping);
        }

        return response()->download(
            $temporaryFile,
            $filename.'.docx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
        )->deleteFileAfterSend(true);
    }

    private function validateFilters(Request $request): void
    {
        $request->validate([
            'status' => ['nullable', Rule::in(['draft', 'submitted', 'reviewed', 'approved', 'rejected'])],
            'form_id' => ['nullable', 'integer', 'exists:forms,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'submission_numbers' => ['nullable', 'string', 'max:255', 'regex:/^\s*[1-9]\d*(\s*,\s*[1-9]\d*\s*)*$/'],
            'category_id' => ['nullable', 'integer', 'exists:form_categories,id'],
            'from' => ['nullable', 'date', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:from'],
            'q' => ['nullable', 'string', 'max:100'],
            'orientation' => ['nullable', Rule::in(['portrait', 'landscape'])],
            'sort_by' => ['nullable', Rule::in(['id', 'date'])],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
        ], [
            'to.after_or_equal' => 'Tanggal akhir harus sama atau setelah tanggal awal.',
        ]);
    }

    public function show(FormSubmission $submission): View
    {
        $submission->load([
            'form.category',
            'version.sections.fields.options',
            'user',
            'reviewer',
            'answers',
            'rows.answers',
        ]);

        $answersByField = $submission->answers->whereNull('form_submission_row_id')->keyBy('form_field_id');
        $rowsByField = $submission->rows->groupBy('form_field_id');

        return view('admin.submissions.show', compact('submission', 'answersByField', 'rowsByField'));
    }

    public function review(Request $request, FormSubmission $submission, SubmissionReviewService $reviewService, ActivityLogService $activityLog): RedirectResponse
    {
        try {
            $reviewService->markReviewed($submission, $request->user());
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        $activityLog->log('submission.review', "Menandai submission #{$submission->id} sedang ditinjau", $submission);

        return back()->with('success', 'Submission ditandai sedang ditinjau.');
    }

    public function approve(Request $request, FormSubmission $submission, SubmissionReviewService $reviewService, ActivityLogService $activityLog): RedirectResponse
    {
        try {
            $reviewService->approve($submission, $request->user(), $request->input('review_notes'));
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        $activityLog->log('submission.approve', "Menyetujui submission #{$submission->id}", $submission);

        return back()->with('success', 'Submission disetujui.');
    }

    public function reject(Request $request, FormSubmission $submission, SubmissionReviewService $reviewService, ActivityLogService $activityLog): RedirectResponse
    {
        try {
            $reviewService->reject($submission, $request->user(), $request->input('review_notes'));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        $activityLog->log('submission.reject', "Menolak submission #{$submission->id}", $submission);

        return back()->with('success', 'Submission ditolak.');
    }

    public function requestRevision(Request $request, FormSubmission $submission, SubmissionReviewService $reviewService, ActivityLogService $activityLog): RedirectResponse
    {
        $data = $request->validate([
            'revision_notes' => ['required', 'string', 'max:5000'],
        ], [
            'revision_notes.required' => 'Catatan revisi wajib diisi.',
        ]);

        try {
            $reviewService->requestRevision($submission, $request->user(), $data['revision_notes']);
        } catch (ValidationException $exception) {
            return back()->withErrors(['revision_notes' => collect($exception->errors())->flatten()->first()])->withInput();
        }

        $activityLog->log('submission.request_revision', "Meminta revisi submission #{$submission->id}", $submission);

        return back()->with('success', 'Permintaan revisi berhasil dikirim kepada User.');
    }

    public function destroy(Request $request, FormSubmission $submission, ActivityLogService $activityLog): RedirectResponse
    {
        $submissionId = $submission->id;
        $formTitle = $submission->form?->title ?? 'Form dihapus';
        $userName = $submission->user?->name ?? 'User dihapus';

        DB::transaction(function () use ($submission, $activityLog, $submissionId, $formTitle, $userName) {
            $activityLog->log('submission.delete', "Menghapus permanen submission #{$submissionId} untuk form \"{$formTitle}\" milik \"{$userName}\"");
            $submission->forceDelete();
        });

        $filters = $request->only(['status', 'form_id', 'user_id', 'category_id', 'from', 'to', 'q', 'submission_numbers', 'sort_by', 'sort_order']);

        return redirect()->route('admin.submissions.index', $filters)->with('success', "Submission #{$submissionId} berhasil dihapus permanen.");
    }
}
