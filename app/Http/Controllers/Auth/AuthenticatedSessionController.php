<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\AppSetting;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function start(Request $request): View|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route($request->user()->homeRoute());
        }

        return $this->create();
    }

    public function create(): View
    {
        $whatsapp = AppSetting::valueFor('support_whatsapp', '');
        $whatsappLink = preg_replace('/\D+/', '', $whatsapp);

        return view('auth.login', compact('whatsapp', 'whatsappLink'));
    }

    public function store(LoginRequest $request, ActivityLogService $activityLog): RedirectResponse
    {
        $request->authenticate();
        // URL intended dari kunjungan sebelumnya dapat mengarah ke dashboard
        // role lain dan menyebabkan 403 setelah login berhasil.
        $request->session()->forget('url.intended');
        $request->session()->regenerate();

        $activityLog->log('login', 'Pengguna berhasil login');

        return redirect()->route(Auth::user()->homeRoute());
    }

    public function destroy(Request $request, ActivityLogService $activityLog): RedirectResponse
    {
        $activityLog->log('logout', 'Pengguna logout');

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
