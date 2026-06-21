@extends('layouts.app')

@section('header')
<h2 class="font-bold text-lg text-gray-900">Something went wrong</h2>
@endsection

@section('content')
<div class="max-w-xl mx-auto py-16 text-center text-slate-500 text-sm">
    <p>An error occurred. The details are shown in the dialog.</p>
    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}"
        class="mt-4 inline-flex text-indigo-600 font-semibold hover:text-indigo-800">Go back</a>
</div>
@endsection
