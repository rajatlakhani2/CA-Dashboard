@php
    $inputId = $inputId ?? ('pwd-' . $credential->id);
    $vaultPassword = $credential->display_password;
    $vaultDecryptFailed = ($credential->getAttributes()['password'] ?? null) && $vaultPassword === '';
@endphp
@if($vaultDecryptFailed)
<span class="text-xs text-amber-700" title="Re-save this password — APP_KEY may have changed">Cannot decrypt</span>
@else
<input type="password" readonly value="{{ $vaultPassword }}" class="{{ $inputClass ?? 'bg-transparent border-none text-sm font-mono text-gray-800 p-0 focus:ring-0 w-28 max-w-[12rem]' }}" id="{{ $inputId }}">
<button type="button"
    onclick="credentialVaultTogglePassword(this, '{{ route('credentials.audit', $credential) }}')"
    data-target="{{ $inputId }}"
    class="{{ $toggleClass ?? 'ml-2 text-gray-400 hover:text-indigo-600 inline' }}" title="Toggle Visibility">
    <svg class="h-4 w-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
    </svg>
</button>
<button type="button"
    onclick="credentialVaultCopy(this, '{{ route('credentials.audit', $credential) }}', 'copied_password')"
    data-copy-value="{{ e($vaultPassword) }}"
    class="{{ $copyClass ?? 'ml-2 text-gray-400 hover:text-indigo-600 inline' }}" title="Copy Password">
    <svg class="h-4 w-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
    </svg>
</button>
@endif
