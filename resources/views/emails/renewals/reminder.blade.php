<x-mail::message>
    # Friendly Reminder

    This is a reminder that your **{{ $renewal->category }}** payment for **{{ $renewal->title }}** is due soon.

    **Amount:** ₹ {{ number_format($renewal->amount) }}
    **Due Date:** {{ $renewal->due_date->format('d M Y') }}

    <x-mail::button :url="route('personal-renewals.index')">
        View Details
    </x-mail::button>

    Thanks,<br>
    {{ config('app.name') }}
</x-mail::message>