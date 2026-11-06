<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Form;
use App\Models\FormSubmission;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total_forms' => Form::count(),
            'published_forms' => Form::where('status', Form::STATUS_PUBLISHED)->count(),
            'draft_forms' => Form::where('status', Form::STATUS_DRAFT)->count(),
            'archived_forms' => Form::where('status', Form::STATUS_ARCHIVED)->count(),
            'pending_submissions' => FormSubmission::where('status', FormSubmission::STATUS_SUBMITTED)->count(),
        ];

        // Grafik & statistik submission lengkap (Total, Hari Ini, per Form,
        // per Bulan) menyusul di Phase 9 -- "Menunggu Verifikasi" di atas
        // sudah cukup untuk kebutuhan langsung Phase 6 (tahu ada yang perlu
        // ditinjau, tanpa perlu buka Submission dulu untuk cek).

        return view('admin.dashboard', compact('stats'));
    }
}
