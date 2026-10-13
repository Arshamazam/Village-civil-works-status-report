<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Tehsil;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['roles', 'tehsils'])->paginate(20);
        $roles = Role::all();
        $tehsils = Tehsil::all();

        return view('admin.users.index', compact('users', 'roles', 'tehsils'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string'],
            'designation' => ['nullable', 'string'],
            'role' => ['required', 'string'],
            'tehsils' => ['nullable', 'array'],
            'tehsils.*' => ['exists:tehsils,id'],
            'force_password_change' => ['boolean'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'designation' => $validated['designation'] ?? null,
            'is_active' => true,
            'force_password_change' => $request->boolean('force_password_change', true),
        ]);

        $user->syncRoles([$validated['role']]);
        if (!empty($validated['tehsils'])) {
            $user->tehsils()->sync($validated['tehsils']);
        }

        AuditLog::log('created_user', $user, null, $user->toArray());

        return back()->with('success', "User {$user->name} created successfully.");
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string'],
            'email' => ['required', 'email', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string'],
            'designation' => ['nullable', 'string'],
            'role' => ['required', 'string'],
            'tehsils' => ['nullable', 'array'],
            'tehsils.*' => ['exists:tehsils,id'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $before = $user->toArray();
        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'designation' => $validated['designation'] ?? null,
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);
        $user->syncRoles([$validated['role']]);
        $user->tehsils()->sync($validated['tehsils'] ?? []);

        AuditLog::log('updated_user', $user, $before, $user->toArray());

        return back()->with('success', "User {$user->name} updated.");
    }

    public function toggleActive(User $user)
    {
        $before = $user->is_active;
        $user->update(['is_active' => !$user->is_active]);

        AuditLog::log('toggled_user_active', $user, ['is_active' => $before], ['is_active' => $user->is_active]);

        $status = $user->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "User {$user->name} has been {$status}.");
    }

    public function unlock(User $user)
    {
        $user->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);

        AuditLog::log('unlocked_user', $user);

        return back()->with('success', "User {$user->name} account unlocked.");
    }
}
