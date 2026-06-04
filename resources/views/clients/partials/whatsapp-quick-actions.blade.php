@php
    $phone = preg_replace('/[^0-9]/', '', (string) ($client->primary_contact_phone ?? ''));
    if (strlen($phone) === 10) {
        $phone = '91' . $phone;
    }
    $hasPhone = strlen($phone) >= 10;
@endphp
@if($hasPhone)
<div class="relative flex flex-wrap gap-2" x-data="{ open: false }">
    <button type="button" @click="open = !open"
        class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-700">
        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 2C6.502 2 2 6.516 2 12.067c0 1.83.487 3.633 1.414 5.23L2.007 22l4.897-1.28c1.55.845 3.302 1.29 5.127 1.29h.005c5.53 0 10.031-4.515 10.031-10.067C22.063 6.52 17.561 2 12.031 2z"/></svg>
        WhatsApp
    </button>
    <div x-show="open" @click.away="open = false" x-cloak
         class="absolute z-20 mt-1 w-56 rounded-lg border border-gray-200 bg-white shadow-lg py-1 text-sm">
        @php
            $reminderMsg = urlencode("Hi {$client->name}, gentle reminder from our office regarding pending compliance or documents.");
            $invoiceMsg = urlencode("Hi {$client->name}, please find your invoice details. Contact us for any queries.");
            $paymentMsg = urlencode("Hi {$client->name}, this is a friendly follow-up on outstanding fees. Please let us know if you need assistance.");
            $docMsg = urlencode("Hi {$client->name}, please share the pending documents for your ongoing filing. Thank you.");
        @endphp
        <a href="https://wa.me/{{ $phone }}?text={{ $reminderMsg }}" target="_blank" rel="noopener"
           class="block px-3 py-2 hover:bg-gray-50 text-gray-800">Send reminder</a>
        <a href="https://wa.me/{{ $phone }}?text={{ $invoiceMsg }}" target="_blank" rel="noopener"
           class="block px-3 py-2 hover:bg-gray-50 text-gray-800">Send invoice follow-up</a>
        <a href="https://wa.me/{{ $phone }}?text={{ $paymentMsg }}" target="_blank" rel="noopener"
           class="block px-3 py-2 hover:bg-gray-50 text-gray-800">Payment follow-up</a>
        <a href="https://wa.me/{{ $phone }}?text={{ $docMsg }}" target="_blank" rel="noopener"
           class="block px-3 py-2 hover:bg-gray-50 text-gray-800">Request documents</a>
    </div>
</div>
@endif
