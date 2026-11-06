<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.edit', [
            'whatsapp' => AppSetting::valueFor('support_whatsapp', ''),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'support_whatsapp' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+() .-]*$/'],
        ], [
            'support_whatsapp.regex' => 'Nomor WhatsApp hanya boleh berisi angka, spasi, dan tanda +, -, atau kurung.',
        ]);

        AppSetting::updateOrCreate(
            ['key' => 'support_whatsapp'],
            ['value' => trim($data['support_whatsapp'] ?? '')]
        );

        return back()->with('success', 'Nomor WhatsApp bantuan berhasil diperbarui.');
    }
}
