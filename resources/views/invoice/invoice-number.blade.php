{{-- invoice number and dates section --}}
<table class="invoice__number-section">
    <tr>
        <td width="60%">
            <h1 class="invoice__number-title">{{ __('invoices::invoice.invoice') }} {{ $invoice->getNumber() ?? __('invoices::invoice.concept') }}</h1>
        </td>
        @if($invoice->date !== null)
        <td width="40%" class="invoice__number-dates">
            <table class="invoice__number-dates-table">
                <tr>
                    <td class="invoice__number-dates-label">{{ __('invoices::invoice.invoice_date') }}</td>
                    <td class="invoice__number-dates-value">{{ $invoice->getFormattedDate() }}</td>
                </tr>
                <tr>
                    <td class="invoice__number-dates-label">{{ __('invoices::invoice.due_date') }}</td>
                    <td class="invoice__number-dates-value">{{ $invoice->getFormattedDueDate() }}</td>
                </tr>
            </table>
        </td>
        @endif
    </tr>
</table>
