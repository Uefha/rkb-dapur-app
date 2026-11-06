<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            // Admin harus tetap bisa mengelola aplikasi setelah mengganti
            // email login dari profilnya sendiri. Untuk akun user biasa,
            // perubahan email tetap mewajibkan verifikasi ulang.
            $request->user()->email_verified_at = $request->user()->isAdmin() ? now() : null;
        }

        $request->user()->save();

        return back()->with('status', 'profile-updated');
    }
}
