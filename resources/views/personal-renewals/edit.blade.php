@extends('layouts.app')

@section('header')
<div class="flex justify-between items-center w-full">
    <span>Edit {{ $personalRenewal->categoryLabel() }}</span>
    <form action="{{ route('personal-renewals.destroy', $personalRenewal) }}" method="POST" onsubmit="return confirm('Are you sure?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="text-sm text-red-600 hover:text-red-900">Delete</button>
    </form>
</div>
@endsection

@section('content')
@if($errors->any())
<div class="max-w-2xl mx-auto mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
    {{ $errors->first() }}
</div>
@endif
<div class="max-w-2xl mx-auto bg-bg-card shadow sm:rounded-lg p-6">
    <form action="{{ route('personal-renewals.update', $personalRenewal) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')
        @include('personal-renewals.partials.form-fields', ['renewal' => $personalRenewal])
        <div class="flex justify-between">
            <a href="{{ route('personal-renewals.index') }}" class="text-sm text-text-secondary hover:text-text-main py-2">Cancel</a>
            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                Update
            </button>
        </div>
    </form>
</div>
@endsection
