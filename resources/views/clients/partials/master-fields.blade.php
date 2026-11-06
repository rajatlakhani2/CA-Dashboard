{{-- Client Master extra fields. Expects optional $client. --}}
@php
    $c = $client ?? null;
    $input = 'block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-line placeholder:text-text-secondary focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6';
@endphp
<div class="sm:col-span-6 border-t pt-4">
    <h3 class="text-sm font-semibold text-gray-900">Personal &amp; WhatsApp</h3>
    <p class="text-xs text-gray-500">Used for birthday wishes, reminders and the daily WhatsApp message.</p>
</div>

<div class="sm:col-span-2">
    <label for="date_of_birth" class="block text-sm font-medium leading-6 text-gray-900">Date of birth / incorporation</label>
    <div class="mt-2">
        <input type="date" name="date_of_birth" id="date_of_birth" value="{{ old('date_of_birth', $c?->date_of_birth?->format('Y-m-d')) }}" class="{{ $input }}">
    </div>
</div>

<div class="sm:col-span-2">
    <label for="whatsapp_number" class="block text-sm font-medium leading-6 text-gray-900">WhatsApp number</label>
    <div class="mt-2">
        <input type="tel" name="whatsapp_number" id="whatsapp_number" value="{{ old('whatsapp_number', $c?->whatsapp_number) }}" placeholder="Blank = same as phone" class="{{ $input }}">
    </div>
</div>

<div class="sm:col-span-2 flex items-end">
    <input type="hidden" name="whatsapp_opt_in" value="0">
    <label class="inline-flex items-center gap-2 text-sm text-gray-900 pb-2">
        <input type="checkbox" name="whatsapp_opt_in" value="1" class="rounded border-gray-300 text-indigo-600"
            @checked(old('whatsapp_opt_in', $c?->whatsapp_opt_in))>
        Client agreed to WhatsApp reminders
    </label>
</div>

<div class="sm:col-span-2">
    <label for="city" class="block text-sm font-medium leading-6 text-gray-900">City</label>
    <div class="mt-2"><input type="text" name="city" id="city" value="{{ old('city', $c?->city) }}" class="{{ $input }}"></div>
</div>

<div class="sm:col-span-2">
    <label for="state" class="block text-sm font-medium leading-6 text-gray-900">State</label>
    <div class="mt-2"><input type="text" name="state" id="state" value="{{ old('state', $c?->state) }}" class="{{ $input }}"></div>
</div>

<div class="sm:col-span-2">
    <label for="pincode" class="block text-sm font-medium leading-6 text-gray-900">Pincode</label>
    <div class="mt-2"><input type="text" name="pincode" id="pincode" inputmode="numeric" maxlength="10" value="{{ old('pincode', $c?->pincode) }}" class="{{ $input }}"></div>
</div>

<div class="sm:col-span-3">
    <label for="referred_by" class="block text-sm font-medium leading-6 text-gray-900">Referred by</label>
    <div class="mt-2"><input type="text" name="referred_by" id="referred_by" value="{{ old('referred_by', $c?->referred_by) }}" class="{{ $input }}"></div>
</div>
