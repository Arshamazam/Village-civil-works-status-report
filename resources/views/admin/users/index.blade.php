@extends('layouts.app', ['title' => 'Users & Staff'])

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="sm:flex sm:items-center sm:justify-between pb-6 border-b border-slate-200">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">User & Field Staff Accounts</h1>
            <p class="mt-1 text-sm text-slate-500">Manage field officers, tehsil in-charges, HQ managers, and system administrators.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mt-6">
        <!-- New User Form -->
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
            <h2 class="text-base font-bold text-slate-900 mb-4">Create New Account</h2>
            <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase">Full Name</label>
                    <input type="text" name="name" required placeholder="e.g. Muhammad Ali"
                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm px-3 py-2">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase">Email Address</label>
                    <input type="email" name="email" required placeholder="officer@prmsc.gov.pk"
                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm px-3 py-2">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase">Designation</label>
                    <input type="text" name="designation" placeholder="e.g. SDO / Field Inspector"
                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm px-3 py-2">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase">Phone</label>
                    <input type="text" name="phone" placeholder="0300-1234567"
                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm px-3 py-2">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase">Initial Password</label>
                    <input type="password" name="password" required minlength="8" value="Password@123"
                           class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm px-3 py-2 font-mono">
                    <p class="text-[11px] text-slate-400 mt-1">Forced to change on first login (AUTH-2).</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase">System Role</label>
                    <select name="role" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-sm px-3 py-2">
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase">Assigned Tehsil(s)</label>
                    <select name="tehsils[]" multiple class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm text-xs px-3 py-2 h-28">
                        @foreach($tehsils as $t)
                            <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->code }})</option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-slate-400 mt-1">Hold Cmd/Ctrl to select multiple.</p>
                </div>
                <button type="submit" class="w-full py-2.5 px-4 rounded-lg bg-emerald-600 text-white font-semibold text-sm hover:bg-emerald-500 transition shadow">
                    Create User Account
                </button>
            </form>
        </div>

        <!-- Users Table -->
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 font-semibold uppercase">
                    <tr>
                        <th class="px-4 py-3">Name / Email</th>
                        <th class="px-4 py-3">Role</th>
                        <th class="px-4 py-3">Assigned Tehsils</th>
                        <th class="px-4 py-3">Account Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($users as $u)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <div class="font-bold text-slate-900">{{ $u->name }}</div>
                            <div class="text-slate-500 text-[11px]">{{ $u->email }}</div>
                            <div class="text-slate-400 text-[10px]">{{ $u->designation ?? 'Staff' }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-800">
                                {{ $u->roles->pluck('name')->first() ?? 'None' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            @forelse($u->tehsils as $t)
                                <span class="inline-block bg-emerald-50 text-emerald-800 text-[10px] px-1.5 py-0.5 rounded font-medium mr-1">{{ $t->name }}</span>
                            @empty
                                <span class="text-slate-400 italic">All / None</span>
                            @endforelse
                        </td>
                        <td class="px-4 py-3">
                            @if(!$u->is_active)
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700">Deactivated</span>
                            @elseif($u->locked_until && $u->locked_until->isFuture())
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700">Locked (15m)</span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700">Active</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right space-x-2">
                            @if($u->locked_until && $u->locked_until->isFuture())
                            <form method="POST" action="{{ route('admin.users.unlock', $u) }}" class="inline">
                                @csrf
                                <button type="submit" class="text-amber-600 hover:text-amber-800 font-semibold text-[11px]">Unlock</button>
                            </form>
                            @endif
                            <form method="POST" action="{{ route('admin.users.toggle-active', $u) }}" class="inline">
                                @csrf
                                <button type="submit" class="font-semibold text-[11px] {{ $u->is_active ? 'text-rose-600 hover:text-rose-800' : 'text-emerald-600 hover:text-emerald-800' }}">
                                    {{ $u->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="px-4 py-3 border-t border-slate-100">
                {{ $users->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
