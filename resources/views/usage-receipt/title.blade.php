{{-- usage receipt title and date --}}
<table class="usage-receipt__title-section">
    <tr>
        <td class="usage-receipt__title-cell">
            <h1 class="usage-receipt__title document__text">{{ $usageReceipt->getTitle() }}</h1>
        </td>
        @if($usageReceipt->getDate() !== null)
            <td class="usage-receipt__date-cell">
                <div class="usage-receipt__date-label">{{ __('invoices::usage-receipt.date') }}</div>
                <div>{{ $usageReceipt->getFormattedDate() }}</div>
            </td>
        @endif
    </tr>
</table>
