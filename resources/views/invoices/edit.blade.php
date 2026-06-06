@extends('layouts.app')

@section('header')
@endsection

@section('content')
@include('invoices.partials.form-ui', [
    'mode' => 'edit',
    'formAction' => route('invoices.update', $invoice),
    'invoice' => $invoice,
    'clients' => $clients,
    'defaultGstRate' => $defaultGstRate ?? 18,
    'defaultSacCode' => $defaultSacCode ?? '998231',
    'firmStateCode' => $firmStateCode ?? '',
])
@endsection
