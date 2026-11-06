<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(): View
    {
        $users = User::whereHas('role', fn ($q) => $q->where('name', Role::USER))->withCount('assignedForms')->orderBy('name')->paginate(15);

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('admin.users.create', ['user' => new User()]);
    }

    public function store(Request $request, ActivityLogService $activityLog): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], ['password.confirmed' => 'Konfirmasi kata sandi tidak cocok.']);
        $user = User::create([
            'role_id' => Role::where('name', Role::USER)->firstOrFail()->id,
            'name' => $data['name'], 'email' => mb_strtolower($data['email']),
            'password' => Hash::make($data['password']), 'is_active' => true,
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();
        $activityLog->log('user.create', "Admin membuat akun pengguna \"{$user->name}\"", $user);

        return redirect()->route('admin.users.index')->with('success', 'Akun pengguna berhasil dibuat.');
    }

    public function edit(User $user): View
    {
        abort_unless($user->isUser(), 404);

        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user, ActivityLogService $activityLog): RedirectResponse
    {
        abort_unless($user->isUser(), 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);
        $user->fill(['name' => $data['name'], 'email' => mb_strtolower($data['email'])]);
        if (! empty($data['password'])) $user->password = Hash::make($data['password']);
        $user->save();
        $activityLog->log('user.update', "Admin memperbarui akun pengguna \"{$user->name}\"", $user);

        return redirect()->route('admin.users.index')->with('success', 'Data pengguna berhasil diperbarui.');
    }

    public function status(Request $request, User $user, ActivityLogService $activityLog): RedirectResponse
    {
        abort_unless($user->isUser(), 404);
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $user->update(['is_active' => $data['is_active']]);
        $activityLog->log('user.status', "Admin ".($user->is_active ? 'mengaktifkan' : 'menonaktifkan')." akun \"{$user->name}\"", $user);

        return back()->with('success', 'Status akun berhasil diperbarui.');
    }

    public function destroy(User $user, ActivityLogService $activityLog): RedirectResponse
    {
        abort_unless($user->isUser(), 404);
        $name = $user->name;
        DB::transaction(function () use ($user, $activityLog, $name) {
            $activityLog->log('user.delete', "Admin menghapus akun pengguna \"{$name}\"");
            $user->assignedForms()->detach();
            $user->submissions()->update(['user_id' => null]);
            $user->delete();
        });

        return redirect()->route('admin.users.index')->with('success', "Akun {$name} berhasil dihapus. Riwayat submission tetap tersimpan.");
    }
}
