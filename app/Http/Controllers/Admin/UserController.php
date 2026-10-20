<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::query()
            ->where('school_id', $request->user()->school_id)
            ->orderBy('name')
            ->paginate(20)
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'role_label' => $user->roleLabel(),
                'is_active' => $user->is_active,
                'last_login_at' => $user->last_login_at?->toIso8601String(),
            ]);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'roles' => config('sigap.roles'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Users/Form', [
            'user' => null,
            'roles' => config('sigap.roles'),
            'roleDescriptions' => config('sigap.role_descriptions'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in(array_keys(config('sigap.roles')))],
            'password' => ['required', 'confirmed', Password::min(8)],
            'is_active' => ['boolean'],
        ]);

        $user = User::create([
            ...$validated,
            'school_id' => $request->user()->school_id,
        ]);

        AuditLogger::record('user.created', $user, 'Akun pengguna ditambahkan ('.$user->roleLabel().').');

        return redirect()->route('admin.users.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(Request $request, User $user): Response
    {
        abort_unless($user->school_id === $request->user()->school_id, 404);

        return Inertia::render('Admin/Users/Form', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'is_active' => $user->is_active,
            ],
            'roles' => config('sigap.roles'),
            'roleDescriptions' => config('sigap.role_descriptions'),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->school_id === $request->user()->school_id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in(array_keys(config('sigap.roles')))],
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'is_active' => ['boolean'],
        ]);

        if ($user->id === $request->user()->id) {
            if (! ($validated['is_active'] ?? true)) {
                return back()->withErrors(['is_active' => 'Anda tidak dapat menonaktifkan akun sendiri.']);
            }
            if ($validated['role'] !== $user->role) {
                return back()->withErrors(['role' => 'Peran akun sendiri tidak dapat diubah dari halaman ini.']);
            }
        }

        if (blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        $user->update($validated);
        AuditLogger::record('user.updated', $user, 'Akun pengguna diperbarui ('.$user->roleLabel().').');

        return redirect()->route('admin.users.index')->with('success', 'Pengguna berhasil diperbarui.');
    }
}
