@extends('layouts.app')

@section('header', 'Users & Module Access')

@section('content')
<div class="space-y-8" x-data="{ tab: 'directory' }">
    @if(session('success'))
    <div class="rounded-xl bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <div class="flex flex-wrap gap-2">
        <button type="button" @click="tab = 'directory'"
            :class="tab === 'directory' ? 'bg-indigo-600 text-white' : 'bg-white text-slate-600 border border-slate-200'"
            class="px-5 py-2 rounded-full text-sm font-bold">Staff Directory</button>
        <button type="button" @click="tab = 'create'"
            :class="tab === 'create' ? 'bg-indigo-600 text-white' : 'bg-white text-slate-600 border border-slate-200'"
            class="px-5 py-2 rounded-full text-sm font-bold">+ Create User</button>
        <button type="button" @click="tab = 'access'"
            :class="tab === 'access' ? 'bg-indigo-600 text-white' : 'bg-white text-slate-600 border border-slate-200'"
            class="px-5 py-2 rounded-full text-sm font-bold">Module Access</button>
    </div>

    <!-- Create user -->
    <div x-show="tab === 'create'" x-cloak class="bg-white rounded-3xl shadow-sm border border-slate-100 p-8">
        <h3 class="text-lg font-black text-slate-900">Create login account</h3>
        <p class="text-sm text-slate-500 mt-1">Email, password, and mobile are required. Mobile is used for daily task WhatsApp reminders.</p>
        <form action="{{ route('users.store') }}" method="POST" class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700">Full name</label>
                <input type="text" name="name" required class="mt-1 w-full rounded-lg border-slate-300">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Email</label>
                <input type="email" name="email" required class="mt-1 w-full rounded-lg border-slate-300">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Mobile (WhatsApp)</label>
                <input type="text" name="mobile" required placeholder="919876543210" class="mt-1 w-full rounded-lg border-slate-300">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Role</label>
                <select name="role" required class="mt-1 w-full rounded-lg border-slate-300">
                    @foreach(['partner' => 'Partner', 'associate' => 'Associate', 'article' => 'Article', 'manager' => 'Manager', 'staff' => 'Staff', 'intern' => 'Intern'] as $v => $l)
                    <option value="{{ $v }}">{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Password</label>
                <input type="password" name="password" required minlength="8" class="mt-1 w-full rounded-lg border-slate-300">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Confirm password</label>
                <input type="password" name="password_confirmation" required class="mt-1 w-full rounded-lg border-slate-300">
            </div>
            <div class="md:col-span-2">
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl font-bold text-sm hover:bg-indigo-500">Create user</button>
            </div>
        </form>
    </div>

    <!-- Module access -->
    <div x-show="tab === 'access'" x-cloak class="space-y-6">
        @foreach($users as $user)
        @if(!$user->isPartner())
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h4 class="font-black text-slate-900">{{ $user->name }}</h4>
                    <p class="text-xs text-slate-500">{{ $user->email }} · {{ ucfirst($user->role) }}</p>
                </div>
            </div>
            <form action="{{ route('users.update-module-access', $user) }}" method="POST">
                @csrf @method('PATCH')
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                    @foreach($modules as $key => $label)
                    <label class="flex items-center gap-2 text-sm text-slate-700 bg-slate-50 rounded-lg px-3 py-2 cursor-pointer hover:bg-indigo-50">
                        <input type="checkbox" name="modules[{{ $key }}]" value="1"
                            {{ ($user->resolvedModuleAccess()[$key] ?? false) ? 'checked' : '' }}
                            class="rounded border-slate-300 text-indigo-600">
                        <span>{{ $label }}</span>
                    </label>
                    @endforeach
                </div>
                <button type="submit" class="mt-4 text-sm font-bold text-indigo-600 hover:text-indigo-800">Save access for {{ $user->name }}</button>
            </form>
        </div>
        @endif
        @endforeach
        <p class="text-sm text-slate-500">Rajat (partner) always has full access and is not listed here.</p>
    </div>

    <!-- Directory -->
    <div x-show="tab === 'directory'" class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="px-8 py-6 border-b border-slate-50">
            <h3 class="font-black text-slate-900 text-xl">Active users</h3>
            <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mt-1">Role, mobile & reminders</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-slate-400 text-[10px] font-black uppercase tracking-widest">
                    <tr>
                        <th class="px-8 py-4">Member</th>
                        <th class="px-8 py-4">Role & mobile</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($users as $user)
                    <tr class="hover:bg-indigo-50/30">
                        <td class="px-8 py-5">
                            <div class="font-black text-slate-900">{{ $user->name }}</div>
                            <div class="text-xs text-slate-500">{{ $user->email }}</div>
                        </td>
                        <td class="px-8 py-5">
                            <form action="{{ route('users.update-role', $user) }}" method="POST" class="flex flex-wrap gap-2 items-center">
                                @csrf @method('PATCH')
                                <input type="text" name="mobile" value="{{ $user->mobile }}" required placeholder="Mobile *"
                                    class="rounded-lg border-slate-200 text-sm w-36">
                                <select name="role" onchange="this.form.submit()" class="rounded-lg border-slate-200 text-sm font-bold">
                                    @foreach(['partner', 'associate', 'article', 'manager', 'staff', 'intern'] as $r)
                                    <option value="{{ $r }}" {{ strtolower((string)$user->role) === $r ? 'selected' : '' }}>{{ ucfirst($r) }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
