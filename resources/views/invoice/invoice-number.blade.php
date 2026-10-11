{{-- invoice title and dates; a concept has no dates, and the due date only shows while payment is due --}}
<table class="invoice__number-section">
    <tr>
        <td class="invoice__number-cell">
            <h1 class="invoice__number-title document__text">{{ $invoice->getTitle() }}</h1>
        </td>
        @if($invoice->isIssued())
            <td class="invoice__number-dates">
                <table class="invoice__number-dates-table">
                    <tr>
                        <td class="invoice__number-dates-label">{{ __('invoices::invoice.invoice_date') }}</td>
                        <td class="invoice__number-dates-value">{{ $invoice->getFormattedDate() }}</td>
                    </tr>
                    @if($invoice->isPaymentDue())
                        <tr>
                            <td class="invoice__number-dates-label">{{ __('invoices::invoice.due_date') }}</td>
                            <td class="invoice__number-dates-value">{{ $invoice->getFormattedDueDate() }}</td>
                        </tr>
                    @endif
                </table>
            </td>
        @endif
    </tr>
</table>
