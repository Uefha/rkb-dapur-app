<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        $whatsapp = AppSetting::valueFor('support_whatsapp', '');
        $whatsappLink = preg_replace('/\D+/', '', $whatsapp);

        $recoveryEnabled = filled(config('auth.password_recovery_key'));

        return view('auth.forgot-password', compact('whatsapp', 'whatsappLink', 'recoveryEnabled'));
    }

    public function recover(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'recovery_key' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'recovery_key.required' => 'Kata kunci pemulihan wajib diisi.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        $configuredKey = (string) config('auth.password_recovery_key');
        if ($configuredKey === '') {
            return back()->withErrors(['recovery_key' => 'Fitur pemulihan kata kunci belum dikonfigurasi.']);
        }

        if (! hash_equals($configuredKey, $validated['recovery_key'])) {
            return back()->withErrors(['recovery_key' => 'Kata kunci pemulihan tidak sesuai.'])->withInput($request->only('email'));
        }

        $user = User::where('email', $validated['email'])->first();
        if ($user) {
            $user->forceFill(['password' => Hash::make($validated['password'])])->save();
        }

        return redirect()->route('login')->with('status', 'Jika email terdaftar, kata sandi telah diperbarui. Silakan masuk dengan kata sandi baru.');
    }
}
