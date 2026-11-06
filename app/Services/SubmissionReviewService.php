<?php

namespace App\Services;

use App\Models\FormSubmission;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Transisi status submission oleh Admin. Draft User TIDAK PERNAH bisa masuk
 * ke sini -- hanya submission yang sudah benar-benar dikirim (submitted,
 * reviewed, approved, rejected) yang boleh ditinjau/disetujui/ditolak.
 */
class SubmissionReviewService
{
    private const REVIEWABLE_FROM = [
        FormSubmission::STATUS_SUBMITTED,
        FormSubmission::STATUS_REVIEWED,
    ];

    public function markReviewed(FormSubmission $submission, User $reviewer): FormSubmission
    {
        $this->guardReviewable($submission);

        $submission->update([
            'status' => FormSubmission::STATUS_REVIEWED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'revision_requested_at' => null,
        ]);

        return $submission->fresh();
    }

    public function approve(FormSubmission $submission, User $reviewer, ?string $notes = null): FormSubmission
    {
        $this->guardReviewable($submission);

        $submission->update([
            'status' => FormSubmission::STATUS_APPROVED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $notes,
            'revision_requested_at' => null,
        ]);

        return $submission->fresh();
    }

    /**
     * @throws ValidationException jika $notes kosong -- penolakan tanpa
     *   alasan tidak berguna bagi User yang mengisi, beda dengan approve
     *   yang catatannya opsional.
     */
    public function reject(FormSubmission $submission, User $reviewer, ?string $notes): FormSubmission
    {
        $this->guardReviewable($submission);

        if (trim((string) $notes) === '') {
            throw ValidationException::withMessages([
                'review_notes' => 'Catatan penolakan wajib diisi supaya User tahu apa yang perlu diperbaiki.',
            ]);
        }

        $submission->update([
            'status' => FormSubmission::STATUS_REJECTED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $notes,
            'revision_requested_at' => null,
        ]);

        return $submission->fresh();
    }

    public function requestRevision(FormSubmission $submission, User $reviewer, ?string $notes): FormSubmission
    {
        $this->guardReviewable($submission);

        if (trim((string) $notes) === '') {
            throw ValidationException::withMessages([
                'review_notes' => 'Catatan revisi wajib diisi agar User tahu bagian yang perlu diperbaiki.',
            ]);
        }

        $submission->update([
            'status' => FormSubmission::STATUS_REJECTED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $notes,
            'revision_requested_at' => now(),
        ]);

        return $submission->fresh();
    }

    private function guardReviewable(FormSubmission $submission): void
    {
        if (! in_array($submission->status, self::REVIEWABLE_FROM, true)) {
            throw ValidationException::withMessages([
                'status' => 'Submission ini sudah '.$submission->statusLabel().', tidak bisa diproses ulang dari sini.',
            ]);
        }
    }
}
