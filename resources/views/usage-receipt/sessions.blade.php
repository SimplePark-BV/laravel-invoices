{{-- usage sessions; fixed column widths keep long texts from pushing the prices off the page --}}
<table class="usage-receipt__sessions">
    <thead>
        <tr>
            <th class="usage-receipt__sessions-header" style="width: 17%">{{ __('invoices::usage-receipt.table.user') }}</th>
            <th class="usage-receipt__sessions-header" style="width: 16%">{{ __('invoices::usage-receipt.table.identifier') }}</th>
            <th class="usage-receipt__sessions-header" style="width: 19%">{{ __('invoices::usage-receipt.table.start_date') }}</th>
            <th class="usage-receipt__sessions-header" style="width: 19%">{{ __('invoices::usage-receipt.table.end_date') }}</th>
            <th class="usage-receipt__sessions-header" style="width: 15%">{{ __('invoices::usage-receipt.table.category') }}</th>
            <th class="usage-receipt__sessions-header usage-receipt__sessions-header--right" style="width: 14%">{{ __('invoices::usage-receipt.table.price') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($usageReceipt->getItems() as $item)
            <tr>
                <td class="usage-receipt__sessions-cell document__text">{{ $item->getUser() }}</td>
                <td class="usage-receipt__sessions-cell document__text">{{ $item->getIdentifier() }}</td>
                <td class="usage-receipt__sessions-cell document__nowrap">{{ $format->date($item->getStartDate(), $usageReceipt->getDateTimeFormat()) }}</td>
                <td class="usage-receipt__sessions-cell document__nowrap">{{ $format->date($item->getEndDate(), $usageReceipt->getDateTimeFormat()) }}</td>
                <td class="usage-receipt__sessions-cell document__text">{{ $item->getCategory() }}</td>
                <td class="usage-receipt__sessions-cell usage-receipt__sessions-cell--right document__amount">{{ $format->money($item->getPriceCents() ?? 0) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

{{-- the cost breakdown when the receipt has lines, then the total, then the tax the total includes --}}
<table class="usage-receipt__totals">
    @foreach($usageReceipt->getLines() as $line)
        <tr>
            <td class="usage-receipt__totals-label document__text">{{ $line->getLabel() }}</td>
            <td class="usage-receipt__totals-value document__amount">{{ $format->money($line->getAmountCents() ?? 0) }}</td>
        </tr>
    @endforeach
    <tr>
        <td class="usage-receipt__totals-label usage-receipt__totals-label--total">{{ __('invoices::usage-receipt.total') }}</td>
        <td class="usage-receipt__totals-value usage-receipt__totals-value--total document__amount">{{ $format->money($totals->getTotalCents()) }}</td>
    </tr>
    @foreach($totals->getTaxGroups() as $taxGroup)
        <tr class="usage-receipt__totals-tax">
            <td class="usage-receipt__totals-label">{{ __('invoices::usage-receipt.tax', ['percentage' => $format->number($taxGroup->getPercentage(), 2)]) }}</td>
            <td class="usage-receipt__totals-value document__amount">{{ $format->money($taxGroup->getTaxCents()) }}</td>
        </tr>
    @endforeach
</table>
