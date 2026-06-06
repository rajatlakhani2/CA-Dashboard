@extends('layouts.app')

@section('header')
@endsection

@section('content')
@include('invoices.partials.form-ui', [
    'mode' => 'create',
    'formAction' => route('invoices.store'),
    'clients' => $clients,
    'nextInvoiceNumber' => $nextInvoiceNumber,
    'prefillItems' => $prefillItems ?? [],
    'prefillDues' => $prefillDues ?? null,
    'selectedClient' => $selectedClient ?? null,
    'linkedTask' => $linkedTask ?? null,
    'defaultGstRate' => $defaultGstRate ?? 18,
    'defaultSacCode' => $defaultSacCode ?? '998231',
    'firmStateCode' => $firmStateCode ?? '',
])
@endsection
