<x-mail::message>
# {{ $renewal->daysLeft() < 0 ? 'Payment overdue' : 'Friendly reminder' }}

Your **{{ $renewal->categoryLabel() }}** payment for **{{ $renewal->title }}** is {{ $renewal->daysLeft() < 0 ? 'overdue' : ($renewal->daysLeft() === 0 ? 'due today' : 'due in '.$renewal->daysLeft().' day(s)') }}.

**Amount:** ₹ {{ number_format((float) $renewal->amount) }}<br>
**Due date:** {{ $renewal->due_date->format('d M Y') }}
@if($renewal->insurer)<br>**Company:** {{ $renewal->insurer }}@endif
@if($renewal->policy_number)<br>**Policy no.:** {{ $renewal->policy_number }}@endif
@if($renewal->insured_person)<br>**Insured:** {{ $renewal->insured_person }}@endif

<x-mail::button :url="route('personal-renewals.index')">
View details
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
