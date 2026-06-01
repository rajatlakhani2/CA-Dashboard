@extends('layouts.app')

@section('header')
The Pulse (Activity Feed)
@endsection

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="flow-root">
        <ul role="list" class="-mb-8">
            @forelse($activities as $activity)
            <li>
                <div class="relative pb-8">
                    @if(!$loop->last)
                    <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-line" aria-hidden="true"></span>
                    @endif
                    <div class="relative flex space-x-3">
                        <div>
                            <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-bg-body
                                {{ $activity->event == 'created' ? 'bg-green-500' : ($activity->event == 'updated' ? 'bg-blue-500' : 'bg-gray-500') }}">
                                @if($activity->event == 'created')
                                <svg class="h-5 w-5 text-white" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                                </svg>
                                @elseif($activity->event == 'updated')
                                <svg class="h-5 w-5 text-white" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                </svg>
                                @else
                                <svg class="h-5 w-5 text-white" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                                @endif
                            </span>
                        </div>
                        <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                            <div>
                                <p class="text-sm text-text-main">
                                    <span class="font-medium text-text-main">{{ $activity->causer?->name ?? 'System' }}</span>
                                    <span class="text-text-secondary">{{ $activity->description }}</span>
                                    <span class="font-medium text-text-main">
                                        @if($activity->subject)
                                        {{ class_basename($activity->subject_type) }}
                                        @if(isset($activity->subject->name))
                                        : {{ $activity->subject->name }}
                                        @elseif(isset($activity->subject->portal_name))
                                        : {{ $activity->subject->portal_name }}
                                        @elseif(isset($activity->subject->title))
                                        : {{ $activity->subject->title }}
                                        @elseif(isset($activity->subject->invoice_number))
                                        : {{ $activity->subject->invoice_number }}
                                        @endif
                                        @elseif($activity->properties && $activity->properties->has('portal_name'))
                                        ClientCredential : {{ $activity->properties['portal_name'] }}
                                        @else
                                        {{ class_basename($activity->subject_type) }} #{{ $activity->subject_id }}
                                        @endif
                                    </span>
                                </p>
                                @if($activity->properties && ($activity->properties->has('attributes') || $activity->properties->has('field')))
                                <div class="mt-2 text-xs text-text-secondary bg-bg-card p-2 rounded border border-line">
                                    @if($activity->properties->has('field'))
                                    <span class="font-medium">field:</span> {{ $activity->properties['field'] }}<br>
                                    @endif
                                    @if($activity->properties->has('client_name'))
                                    <span class="font-medium">client:</span> {{ $activity->properties['client_name'] }}<br>
                                    @endif
                                    @foreach($activity->properties->get('attributes', []) as $key => $val)
                                    <span class="font-medium">{{ $key }}:</span> {{ is_array($val) ? json_encode($val) : $val }}<br>
                                    @endforeach
                                </div>
                                @endif
                            </div>
                            <div class="whitespace-nowrap text-right text-xs text-text-secondary">
                                <time datetime="{{ $activity->created_at }}">{{ $activity->created_at->diffForHumans() }}</time>
                            </div>
                        </div>
                    </div>
                </div>
            </li>
            @empty
            <li class="py-4 text-center text-sm text-text-secondary">No activity recorded yet. Start working to see the pulse!</li>
            @endforelse
        </ul>
        <div class="mt-4">
            {!! $activities->links() !!}
        </div>
    </div>
</div>
@endsection