{{-- usage sessions; fixed column widths keep long texts from pushing the prices off the page. The price column can be hidden, the last column is then right-aligned so the table does not end in blank space --}}
@php($withPrices = $usageReceipt->showsItemPrices())
@php($widths = $withPrices ? [17, 16, 19, 19, 15, 14] : [20, 19, 21, 21, 19])

<table class="usage-receipt__sessions">
    <thead>
        <tr>
            <th class="usage-receipt__sessions-header" style="width: {{ $widths[0] }}%">{{ __('invoices::usage-receipt.table.user') }}</th>
            <th class="usage-receipt__sessions-header" style="width: {{ $widths[1] }}%">{{ __('invoices::usage-receipt.table.identifier') }}</th>
            <th class="usage-receipt__sessions-header" style="width: {{ $widths[2] }}%">{{ __('invoices::usage-receipt.table.start_date') }}</th>
            <th class="usage-receipt__sessions-header" style="width: {{ $widths[3] }}%">{{ __('invoices::usage-receipt.table.end_date') }}</th>
            <th class="usage-receipt__sessions-header{{ $withPrices ? '' : ' usage-receipt__sessions-header--right' }}" style="width: {{ $widths[4] }}%">{{ __('invoices::usage-receipt.table.category') }}</th>
            @if($withPrices)
                <th class="usage-receipt__sessions-header usage-receipt__sessions-header--right" style="width: {{ $widths[5] }}%">{{ __('invoices::usage-receipt.table.price') }}</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @foreach($usageReceipt->getItems() as $item)
            <tr>
                <td class="usage-receipt__sessions-cell document__text">{{ $item->getUser() }}</td>
                <td class="usage-receipt__sessions-cell document__text">{{ $item->getIdentifier() }}</td>
                <td class="usage-receipt__sessions-cell document__nowrap">{{ $format->date($item->getStartDate(), $usageReceipt->getDateTimeFormat()) }}</td>
                <td class="usage-receipt__sessions-cell document__nowrap">{{ $format->date($item->getEndDate(), $usageReceipt->getDateTimeFormat()) }}</td>
                <td class="usage-receipt__sessions-cell{{ $withPrices ? '' : ' usage-receipt__sessions-cell--right' }} document__text">{{ $item->getCategory() }}</td>
                @if($withPrices)
                    <td class="usage-receipt__sessions-cell usage-receipt__sessions-cell--right document__amount">{{ $format->money($item->getPriceCents() ?? 0) }}</td>
                @endif
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
