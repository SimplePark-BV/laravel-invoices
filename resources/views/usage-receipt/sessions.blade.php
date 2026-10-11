{{-- usage sessions table --}}
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
        @foreach($usageReceipt->getItems() as $item)
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

{{-- cost breakdown (optional - only shown when the receipt has lines) --}}
@if($usageReceipt->getLines()->isNotEmpty())
<table class="usage-receipt__lines">
    @foreach($usageReceipt->getLines() as $line)
    <tr>
        <td class="usage-receipt__lines-label">{{ $line->getLabel() }}</td>
        <td class="usage-receipt__lines-value">{{ $line->getFormattedAmount() }}</td>
    </tr>
    @endforeach
    {{-- tax included in the lines (only shown when a line has a tax percentage) --}}
    @if($usageReceipt->getLinesTaxGroups()->isNotEmpty())
    <tr>
        <td class="usage-receipt__lines-label">{{ __('invoices::usage-receipt.tax') }}</td>
        <td class="usage-receipt__lines-value">{{ \SimpleParkBv\Invoices\Services\CurrencyFormatter::format($usageReceipt->getLinesTaxAmount()) }}</td>
    </tr>
    @endif
</table>
@endif

{{-- total section --}}
<table class="usage-receipt__total">
    <tr>
        <td class="usage-receipt__total-label">{{ __('invoices::usage-receipt.total') }}</td>
        <td class="usage-receipt__total-value">{{ $usageReceipt->getFormattedTotal() }}</td>
    </tr>
</table>
