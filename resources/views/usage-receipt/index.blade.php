@extends('invoices::layout')

@section('body-class', 'usage-receipt')

@section('content')
    {{-- the footer comes first, so dompdf repeats it at the bottom of every page --}}
    @include('invoices::usage-receipt.footer')

    @include('invoices::usage-receipt.header')

    @include('invoices::usage-receipt.title')

    @include('invoices::partials.buyer')

    @include('invoices::usage-receipt.ids')

    @include('invoices::usage-receipt.sessions')

    @include('invoices::usage-receipt.notes')
@endsection
