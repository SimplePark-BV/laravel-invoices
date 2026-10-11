@extends('invoices::layout')

@section('body-class', 'invoice')

@section('content')
    {{-- the footer comes first, so dompdf repeats it at the bottom of every page --}}
    @include('invoices::invoice.footer')

    @include('invoices::invoice.header')

    @include('invoices::partials.buyer')

    @include('invoices::invoice.invoice-number')

    @include('invoices::invoice.items')
@endsection
