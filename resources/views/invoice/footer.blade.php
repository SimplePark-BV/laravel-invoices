{{-- invoice footer --}}
@if($invoice->date !== null)
<div class="invoice__footer">
    {!! $invoice->getFooterMessage() !!}
</div>
@endif
