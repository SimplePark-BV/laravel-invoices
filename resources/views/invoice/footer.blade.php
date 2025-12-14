{{-- invoice footer --}}
<div class="invoice__footer">
    We verzoeken je vriendelijk het bovenstaande bedrag van 
    <span class="invoice__footer-amount">€ {{ number_format($invoice->total(), 2, ',', '.') }}</span> 
    voor <span class="invoice__footer-date">{{ $invoice->date->addDays($invoice->pay_until_days)->format('d-m-Y') }}</span> 
    te voldoen op onze bankrekening. Voor vragen kan je contact opnemen per e-mail.
</div>
