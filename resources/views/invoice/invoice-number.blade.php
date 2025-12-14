{{-- invoice number and dates section --}}
<table class="invoice__number-section">
    <tr>
        <td width="60%">
            <h1 class="invoice__number-title">Factuur {{ $invoice->number ?? 'CONCEPT' }}</h1>
        </td>
        <td width="40%" class="invoice__number-dates">
            <table class="invoice__number-dates-table">
                <tr>
                    <td class="invoice__number-dates-label">Factuurdatum:</td>
                    <td class="invoice__number-dates-value">{{ $invoice->date->format('d-m-Y') }}</td>
                </tr>
                <tr>
                    <td class="invoice__number-dates-label">Vervaldatum:</td>
                    <td class="invoice__number-dates-value">{{ $invoice->date->addDays($invoice->pay_until_days)->format('d-m-Y') }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>
