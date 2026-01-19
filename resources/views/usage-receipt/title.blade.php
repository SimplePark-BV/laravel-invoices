{{-- usage receipt title and date section --}}
<table class="usage-receipt__title-section">
    <tr>
        <td class="usage-receipt__title-cell">
            <h1 class="usage-receipt__title">{{ $usageReceipt->getTitle() }}</h1>
        </td>
        <td class="usage-receipt__date-cell">
            <div class="usage-receipt__date-container">
                <div class="usage-receipt__date-label">{{ __('invoices::usage-receipt.date') }}</div>
                <div>{{ $usageReceipt->getFormattedDate() }}</div>
            </div>
        </td>
    </tr>
</table>
