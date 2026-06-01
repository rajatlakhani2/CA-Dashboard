@extends('layouts.app')

@section('header', 'Staff Directory')

@section('content')
<div class="max-w-7xl mx-auto space-y-6" x-data="{ showRegisterModal: false }">

    <!-- Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
        <div>
            <h3 class="text-lg font-bold text-gray-900">Manage Your Team</h3>
            <p class="text-xs text-gray-500 mt-1">Assign tasks, track performance, and send WhatsApp reminders</p>
        </div>
        <button @click="showRegisterModal = true" class="inline-flex items-center justify-center px-4 py-2.5 border border-transparent text-sm font-bold rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-500/20 transition-all hover:scale-[1.02]">
            <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
            </svg>
            Register New Staff
        </button>
    </div>

    <!-- Staff Directory Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($employees as $employee)
        <div class="group relative bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300 hover:border-indigo-100 flex flex-col justify-between">
            <div>
                <!-- Top Info -->
                <div class="flex items-start space-x-4">
                    <div class="flex-shrink-0">
                        <span class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-indigo-600 shadow-md text-white text-xl font-bold">
                            {{ substr($employee->name, 0, 1) }}
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('staff.show', $employee) }}" class="text-lg font-bold text-gray-900 group-hover:text-indigo-600 truncate transition-colors">
                                {{ $employee->name }}
                            </a>
                        </div>
                        <p class="text-xs text-gray-500 truncate mt-0.5">{{ $employee->email }}</p>
                        @if($employee->mobile)
                        <p class="text-[10px] text-gray-400 font-medium mt-0.5">📞 {{ $employee->mobile }}</p>
                        @endif

                        <!-- Role Badge -->
                        <div class="mt-2">
                            @php
                                $roleColors = [
                                    'partner' => 'bg-indigo-50 text-indigo-700 border-indigo-100',
                                    'manager' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                    'staff' => 'bg-violet-50 text-violet-700 border-violet-100',
                                    'intern' => 'bg-amber-50 text-amber-700 border-amber-100',
                                ];
                                $roleColor = $roleColors[strtolower($employee->role ?? '')] ?? 'bg-gray-50 text-gray-700 border-gray-100';
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider border {{ $roleColor }}">
                                {{ $employee->role ?? 'Staff' }}
                            </span>
                            @if($employee->branch)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold text-gray-500 bg-gray-100 ml-1">
                                🏢 {{ $employee->branch->name }}
                            </span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Stats Overview -->
                <div class="mt-6 grid grid-cols-2 gap-4 border-t border-gray-50 pt-4">
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Active Tasks</p>
                        <p class="mt-1 text-2xl font-black text-slate-800">{{ $employee->tasks_count }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Clients Mgd.</p>
                        <p class="mt-1 text-2xl font-black text-slate-800">{{ $employee->managed_clients_count }}</p>
                    </div>
                </div>
            </div>

            <!-- WhatsApp Actions -->
            <div class="mt-6 border-t border-gray-50 pt-4">
                @if($employee->tasks_count > 0 && !empty($employee->mobile))
                <form action="{{ route('staff.send-reminder', $employee) }}" method="POST">
                    @csrf
                    <input type="hidden" name="type" value="summary">
                    <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2.5 border border-indigo-100 rounded-xl text-xs font-bold text-indigo-600 bg-indigo-50/50 hover:bg-indigo-600 hover:text-white transition-all shadow-sm">
                        <svg class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        Send WhatsApp Reminder
                    </button>
                </form>
                @elseif(empty($employee->mobile))
                <div class="text-center text-gray-400 text-[11px] py-2 border border-dashed border-gray-200 rounded-xl bg-gray-50/50">
                    No Mobile Number Registered
                </div>
                @else
                <div class="text-center text-gray-400 text-[11px] py-2 border border-dashed border-gray-200 rounded-xl bg-gray-50/50">
                    No Pending Tasks
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    <!-- Registration Modal -->
    <div x-show="showRegisterModal" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         aria-labelledby="modal-title" 
         role="dialog" 
         aria-modal="true" 
         style="display: none;">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div x-show="showRegisterModal" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="showRegisterModal = false"
                 class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                 aria-hidden="true"></div>

            <!-- Position modal in center -->
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showRegisterModal" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100">
                
                <div class="bg-white px-6 pt-6 pb-4 sm:p-8">
                    <div class="flex justify-between items-center pb-4 border-b border-gray-100 mb-6">
                        <h3 class="text-lg font-bold text-gray-900" id="modal-title">
                            Register Staff Member
                        </h3>
                        <button @click="showRegisterModal = false" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form action="{{ route('staff.store') }}" method="POST" class="space-y-4">
                        @csrf
                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-xs font-bold uppercase tracking-wider text-gray-700">Full Name *</label>
                            <input type="text" name="name" id="name" required class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2.5 px-3">
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-xs font-bold uppercase tracking-wider text-gray-700">Email Address *</label>
                            <input type="email" name="email" id="email" required class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2.5 px-3">
                        </div>

                        <!-- Mobile -->
                        <div>
                            <label for="mobile" class="block text-xs font-bold uppercase tracking-wider text-gray-700">Mobile Number (WhatsApp) *</label>
                            <input type="text" name="mobile" id="mobile" placeholder="e.g. 919876543210" class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2.5 px-3">
                            <p class="text-[10px] text-gray-400 mt-1">Include country code without special characters (e.g. 91 for India)</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <!-- Role -->
                            <div>
                                <label for="role" class="block text-xs font-bold uppercase tracking-wider text-gray-700">Role *</label>
                                <select name="role" id="role" required class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2.5 px-3 bg-white">
                                    <option value="staff">Staff</option>
                                    <option value="intern">Intern</option>
                                    @if(auth()->user()->isPartner())
                                    <option value="manager">Manager</option>
                                    <option value="partner">Partner</option>
                                    @endif
                                </select>
                            </div>

                            <!-- Branch -->
                            <div>
                                <label for="branch_id" class="block text-xs font-bold uppercase tracking-wider text-gray-700">Branch</label>
                                <select name="branch_id" id="branch_id" class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2.5 px-3 bg-white">
                                    <option value="">-- No Branch --</option>
                                    @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Password -->
                        <div>
                            <label for="password" class="block text-xs font-bold uppercase tracking-wider text-gray-700">Password *</label>
                            <input type="password" name="password" id="password" required minlength="8" class="mt-1 block w-full rounded-xl border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2.5 px-3">
                        </div>

                        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-100 mt-6">
                            <button type="button" @click="showRegisterModal = false" class="px-4 py-2 text-sm font-bold text-gray-700 bg-gray-50 hover:bg-gray-100 rounded-xl transition-all">
                                Cancel
                            </button>
                            <button type="submit" class="px-5 py-2 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-md shadow-indigo-500/20 transition-all">
                                Register Staff
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
