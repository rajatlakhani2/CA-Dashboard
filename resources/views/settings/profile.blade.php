@extends('layouts.app')

@section('header')
<div class="flex justify-between items-center w-full">
    <span>User Settings</span>
</div>
@endsection

@section('content')
<div class="max-w-3xl mx-auto">
    <form action="{{ route('settings.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="bg-white shadow sm:rounded-lg overflow-hidden">
            <div class="px-4 py-5 sm:p-6 space-y-6">

                <div>
                    <h3 class="text-lg font-medium leading-6 text-gray-900">Profile Information</h3>
                    <p class="mt-1 text-sm text-gray-500">Update your account's profile information and email address.</p>
                </div>

                <div class="grid grid-cols-6 gap-6">
                    <div class="col-span-6 sm:col-span-4">
                        <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>

                    <div class="col-span-6 sm:col-span-4">
                        <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                        <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>

                    <div class="col-span-6 sm:col-span-4">
                        <label for="theme" class="block text-sm font-medium text-gray-700">Dashboard Theme</label>
                        <select id="theme" name="theme" class="mt-1 block w-full bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="modern" {{ $user->theme == 'modern' ? 'selected' : '' }}>Modern</option>
                            <option value="executive" {{ $user->theme == 'executive' ? 'selected' : '' }}>Executive</option>
                            <option value="dense" {{ $user->theme == 'dense' ? 'selected' : '' }}>Dense</option>
                        </select>
                        <p class="mt-2 text-sm text-gray-500">Select your preferred color scheme and density.</p>
                    </div>
                </div>

                <div class="border-t border-gray-200 pt-6">
                    <div>
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Interface Settings</h3>
                        <p class="mt-1 text-sm text-gray-500">Customize the look and feel of your dashboard.</p>
                    </div>

                    <div class="grid grid-cols-6 gap-6 mt-6" x-data="{ 
                        scale: localStorage.getItem('app_scale') || 100,
                        updateScale(val) {
                            this.scale = val;
                            document.documentElement.style.fontSize = val + '%';
                            localStorage.setItem('app_scale', val);
                        }
                    }">
                        <div class="col-span-6 sm:col-span-4">
                            <label for="theme" class="block text-sm font-medium text-gray-700">Dashboard Theme</label>
                            <select id="theme" name="theme" class="mt-1 block w-full bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="modern" {{ $user->theme == 'modern' ? 'selected' : '' }}>Modern</option>
                                <option value="executive" {{ $user->theme == 'executive' ? 'selected' : '' }}>Executive</option>
                                <option value="dense" {{ $user->theme == 'dense' ? 'selected' : '' }}>Dense</option>
                            </select>
                            <p class="mt-2 text-sm text-gray-500">Controls the color density and spacing.</p>
                        </div>

                        <div class="col-span-6 sm:col-span-4">
                            <label for="font_scale" class="block text-sm font-medium text-gray-700">
                                Font Size: <span x-text="scale + '%'"></span>
                            </label>
                            <input
                                type="range"
                                id="font_scale"
                                min="85"
                                max="115"
                                step="5"
                                x-model="scale"
                                @input="updateScale($event.target.value)"
                                class="w-full mt-2 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
                            <div class="flex justify-between text-xs text-gray-500 mt-1">
                                <span>Small</span>
                                <span>Default</span>
                                <span>Large</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-200 pt-6">
                    <div>
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Company Details</h3>
                        <p class="mt-1 text-sm text-gray-500">These details will appear on your Invoices.</p>
                    </div>

                    <div class="grid grid-cols-6 gap-6 mt-6">
                        <div class="col-span-6 sm:col-span-4">
                            <label for="company_name" class="block text-sm font-medium text-gray-700">Company Name</label>
                            <input type="text" name="company_name" id="company_name" value="{{ old('company_name', $company_name) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>

                        <div class="col-span-6">
                            <label for="company_address" class="block text-sm font-medium text-gray-700">Address</label>
                            <textarea name="company_address" id="company_address" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">{{ old('company_address', $company_address) }}</textarea>
                        </div>

                        <div class="col-span-6 sm:col-span-4">
                            <label for="company_email" class="block text-sm font-medium text-gray-700">Billing Email</label>
                            <input type="email" name="company_email" id="company_email" value="{{ old('company_email', $company_email) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-200 pt-6">
                    <div>
                        <h3 class="text-lg font-medium leading-6 text-gray-900">Update Password</h3>
                        <p class="mt-1 text-sm text-gray-500">Ensure your account is using a long, random password to stay secure.</p>
                    </div>

                    <div class="grid grid-cols-6 gap-6 mt-6">
                        <div class="col-span-6 sm:col-span-4">
                            <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                            <input type="password" name="current_password" id="current_password" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>

                        <div class="col-span-6 sm:col-span-4">
                            <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                            <input type="password" name="new_password" id="new_password" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>

                        <div class="col-span-6 sm:col-span-4">
                            <label for="new_password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                            <input type="password" name="new_password_confirmation" id="new_password_confirmation" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                    </div>
                </div>

            </div>
            <div class="px-4 py-3 bg-gray-50 text-right sm:px-6">
                <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Save Changes
                </button>
            </div>
        </div>
    </form>
</div>
@endsection