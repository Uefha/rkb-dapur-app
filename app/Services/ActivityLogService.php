<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Layanan pencatatan aktivitas terpusat.
 * Dipanggil dari controller mana pun (auth, form builder, submission, dst.)
 * agar jejak audit (bagian 24 spesifikasi) konsisten dan tidak diduplikasi logikanya.
 */
class ActivityLogService
{
    public function log(string $action, ?string $description = null, ?Model $subject = null, array $properties = []): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'properties' => $properties,
            'ip_address' => Request::ip(),
        ]);
    }
}
