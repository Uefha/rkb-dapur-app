<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $userId = $request->user()->id;

        $publishedFormsCount = Form::where('status', 'published')
            ->where(function ($query) use ($userId) {
                $query->where('visibility', 'all')
                    ->orWhereHas('assignedUsers', fn ($users) => $users->whereKey($userId));
            })
            ->count();

        $submissionCounts = FormSubmission::query()
            ->where('user_id', $userId)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $draftCount = $submissionCounts->get(FormSubmission::STATUS_DRAFT, 0);
        // "Menunggu Verifikasi" cuma menghitung status submitted, sengaja
        // tidak digabung dengan "reviewed" -- keduanya diberi label berbeda
        // di tempat lain (mis. dropdown filter Form Saya), jadi angka di
        // dashboard harus tetap konsisten dengan label yang sama persis.
        $pendingCount = $submissionCounts->get(FormSubmission::STATUS_SUBMITTED, 0);
        $approvedCount = $submissionCounts->get(FormSubmission::STATUS_APPROVED, 0);

        return view('user.dashboard', compact('publishedFormsCount', 'draftCount', 'pendingCount', 'approvedCount'));
    }
}
