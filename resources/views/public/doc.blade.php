@extends('public.layout')

@section('content')
<section class="pb-doc">
    <div class="pb-wrap pb-wrap--narrow">
        <a href="{{ route('landing') }}" class="pb-doc__back">← Back to home</a>
        <article class="pb-doc__card">
            <h1>@yield('doc_title')</h1>
            <p class="pb-doc__updated">@yield('doc_meta', 'Last updated 21 November 2026')</p>
            @yield('doc')
        </article>
    </div>
</section>
@endsection
