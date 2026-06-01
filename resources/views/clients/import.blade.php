@extends('layouts.app')

@section('header', 'Bulk Import Clients')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-[3rem] shadow-2xl border border-gray-100 overflow-hidden animate-enter">
        <div class="bg-indigo-600 p-12 text-white text-center relative overflow-hidden">
            <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-10"></div>
            <div class="relative z-10">
                <h3 class="text-3xl font-black italic tracking-tighter">Data Migration</h3>
                <p class="text-indigo-200 text-xs font-bold uppercase tracking-[0.3em] mt-3">Excel & CSV Bulk Import</p>
            </div>
        </div>

        <div class="p-12">
            <div class="mb-10 p-8 bg-slate-50 rounded-[2rem] border border-slate-100">
                <h4 class="text-slate-900 font-black text-sm uppercase tracking-widest mb-4">Instructions</h4>
                <ul class="space-y-3">
                    <li class="flex items-start text-xs font-bold text-slate-500">
                        <svg class="h-4 w-4 text-indigo-500 mr-3 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                        Download the official template to ensure column mapping is correct.
                    </li>
                    <li class="flex items-start text-xs font-bold text-slate-500">
                        <svg class="h-4 w-4 text-indigo-500 mr-3 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                        Ensure PAN numbers are valid 10-digit alphanumeric codes.
                    </li>
                </ul>
                <div class="mt-8">
                    <a href="{{ route('clients.template') }}" class="inline-flex items-center text-indigo-600 font-black uppercase text-[10px] tracking-widest hover:text-slate-900 transition-colors">
                        <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Download Template (.xlsx)
                    </a>
                </div>
            </div>

            <form action="{{ route('clients.import.preview') }}" method="POST" enctype="multipart/form-data" class="space-y-10">
                @csrf
                <div class="relative h-64 border-4 border-dashed border-slate-100 rounded-[2.5rem] bg-slate-50 hover:bg-white hover:border-indigo-400 transition-all group flex flex-col items-center justify-center cursor-pointer">
                    <input type="file" name="file" required class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                    <div class="text-center group-hover:scale-110 transition-transform">
                        <div class="bg-indigo-100 text-indigo-600 h-16 w-16 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                        </div>
                        <p class="text-slate-900 font-black text-lg">Drop Data File Here</p>
                        <p class="text-slate-400 text-[10px] font-black uppercase tracking-widest mt-1">Accepts Excel or CSV files up to 10MB</p>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-4">
                    <a href="{{ route('clients.index') }}" class="text-slate-400 hover:text-slate-900 font-black uppercase tracking-widest text-[10px]">Cancel</a>
                    <button type="submit" class="bg-slate-900 text-white px-12 py-5 rounded-[1.5rem] font-black uppercase text-sm tracking-[0.2em] shadow-2xl hover:bg-indigo-600 transition-all transform active:scale-95">
                        Start Processing
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection