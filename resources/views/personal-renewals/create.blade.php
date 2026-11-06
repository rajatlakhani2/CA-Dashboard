@extends('layouts.app')

@section('header')
<div class="flex justify-between items-center w-full">
    <span>Add LIC / Insurance / Renewal</span>
    <a href="{{ route('personal-renewals.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">Back to Calendar</a>
</div>
@endsection

@section('content')
@if(session('success'))
<div class="max-w-2xl mx-auto mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
    {{ session('success') }}
</div>
@endif
@if($errors->any())
<div class="max-w-2xl mx-auto mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
    {{ $errors->first() }}
</div>
@endif
<div class="max-w-2xl mx-auto bg-bg-card shadow sm:rounded-lg p-6">
    <form action="{{ route('personal-renewals.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @include('personal-renewals.partials.form-fields', ['renewal' => null])
        <div class="flex justify-end">
            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                Save
            </button>
        </div>
    </form>
</div>
@endsection
