{{-- parking sessions table --}}
<table class="usage-receipt__sessions">
    <thead>
        <tr>
            <th class="usage-receipt__sessions-header">{{ __('invoices::usage-receipt.table.user') }}</th>
            <th class="usage-receipt__sessions-header">{{ __('invoices::usage-receipt.table.identifier') }}</th>
            <th class="usage-receipt__sessions-header">{{ __('invoices::usage-receipt.table.start_date') }}</th>
            <th class="usage-receipt__sessions-header">{{ __('invoices::usage-receipt.table.end_date') }}</th>
            <th class="usage-receipt__sessions-header">{{ __('invoices::usage-receipt.table.category') }}</th>
            <th class="usage-receipt__sessions-header usage-receipt__sessions-header--right">{{ __('invoices::usage-receipt.table.price') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($usageReceipt->items as $item)
        <tr>
            <td class="usage-receipt__sessions-cell">{{ $item->getUser() }}</td>
            <td class="usage-receipt__sessions-cell">{{ $item->getIdentifier() }}</td>
            <td class="usage-receipt__sessions-cell">{{ $item->getFormattedStartDate() }}</td>
            <td class="usage-receipt__sessions-cell">{{ $item->getFormattedEndDate() }}</td>
            <td class="usage-receipt__sessions-cell">{{ $item->getCategory() }}</td>
            <td class="usage-receipt__sessions-cell usage-receipt__sessions-cell--right">{{ $item->getFormattedPrice() }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- total section --}}
<table class="usage-receipt__total">
    <tr>
        <td class="usage-receipt__total-label">{{ __('invoices::usage-receipt.total') }}</td>
        <td class="usage-receipt__total-value">{{ $usageReceipt->getFormattedTotal() }}</td>
    </tr>
</table>
