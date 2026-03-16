@extends('layouts.app')

@section('header', 'User Management & RBAC')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
        <div class="px-10 py-8 border-b border-slate-50 flex items-center justify-between bg-slate-50/20">
            <div>
                <h3 class="font-black text-slate-900 text-xl tracking-tight">Active Staff Directory</h3>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-[0.2em] mt-1">Manage access control and organizational roles</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-slate-400 text-[10px] font-black uppercase tracking-widest">
                    <tr>
                        <th class="px-10 py-6">Member</th>
                        <th class="px-10 py-6">Access Level</th>
                        <th class="px-10 py-6">Status</th>
                        <th class="px-10 py-6 text-right">Permissions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($users as $user)
                    <tr class="hover:bg-indigo-50/20 transition-colors">
                        <td class="px-10 py-7">
                            <div class="flex items-center">
                                <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-black">
                                    {{ substr($user->name, 0, 1) }}
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-black text-slate-900">{{ $user->name }}</div>
                                    <div class="text-[10px] font-bold text-slate-400">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-10 py-7">
                            <form action="{{ route('users.update-role', $user) }}" method="POST">
                                @csrf @method('PATCH')
                                <select name="role" onchange="this.form.submit()" class="bg-slate-50 border-0 rounded-xl text-[10px] font-black uppercase tracking-widest text-slate-700 focus:ring-2 focus:ring-indigo-500 py-2 px-3">
                                    @foreach(['Partner', 'Manager', 'Staff', 'Intern'] as $role)
                                    <option value="{{ $role }}" {{ $user->role == $role ? 'selected' : '' }}>{{ $role }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                        <td class="px-10 py-7">
                            <span class="inline-flex items-center px-4 py-1.5 rounded-full text-[10px] font-black uppercase bg-green-50 text-green-700 border border-green-100 shadow-sm">ACTIVE</span>
                        </td>
                        <td class="px-10 py-7 text-right">
                            <div class="flex items-center justify-end space-x-3 text-slate-400">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Info Box -->
    <div class="bg-indigo-600 rounded-[2.5rem] p-10 text-white relative overflow-hidden shadow-2xl">
        <div class="absolute right-0 top-0 -mr-20 -mt-20 w-80 h-80 bg-white/10 rounded-full blur-3xl"></div>
        <div class="relative z-10">
            <h4 class="text-lg font-black italic">Security Policy Reminder</h4>
            <p class="text-indigo-100 text-sm mt-4 leading-relaxed max-w-2xl font-medium tracking-wide">
                Role-based access control (RBAC) ensures developers and interns only see data relevant to their assigned tasks.
                Partners and Managers have oversight across all financial records and client sensitive data.
            </p>
        </div>
    </div>
</div>
@endsection