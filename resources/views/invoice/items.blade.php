{{-- invoice items and totals; fixed column widths keep long texts from pushing the amounts off the page --}}
@php($columns = ['7%', '48%', '15%', '15%', '15%'])

<table class="invoice__items">
    <thead>
        <tr>
            <th class="invoice__items-header" style="width: {{ $columns[0] }}"></th>
            <th class="invoice__items-header" style="width: {{ $columns[1] }}">{{ __('invoices::invoice.description') }}</th>
            <th class="invoice__items-header invoice__items-header--right" style="width: {{ $columns[2] }}">{{ __('invoices::invoice.price') }}</th>
            <th class="invoice__items-header invoice__items-header--right" style="width: {{ $columns[3] }}">{{ __('invoices::invoice.total') }}</th>
            <th class="invoice__items-header invoice__items-header--right" style="width: {{ $columns[4] }}">{{ __('invoices::invoice.tax') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoice->getItems() as $item)
            <tr>
                <td class="invoice__items-cell document__amount document__amount--left">{{ $format->quantity($item->getQuantity()) }}x</td>
                <td class="invoice__items-cell document__text">
                    <span class="invoice__items-title">{{ $item->getTitle() }}</span>
                    @if($item->getDescription() !== null)
                        <br><span class="invoice__items-description">{!! nl2br(e($item->getDescription())) !!}</span>
                    @endif
                </td>
                <td class="invoice__items-cell document__amount">{{ $format->money($item->getUnitPriceCents() ?? 0) }}</td>
                <td class="invoice__items-cell document__amount">{{ $format->money($item->getTotalCents()) }}</td>
                <td class="invoice__items-cell document__amount">{{ $item->getTaxPercentage() === null ? '' : $format->percentage($item->getTaxPercentage()) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<table class="invoice__totals">
    {{-- an empty first row sets the column widths, the same as the items above --}}
    <tr>
        @foreach($columns as $width)
            <td style="width: {{ $width }}"></td>
        @endforeach
    </tr>
    <tr>
        <td colspan="3" class="invoice__totals-label">{{ __('invoices::invoice.subtotal') }}</td>
        <td class="invoice__totals-value document__amount">{{ $format->money($totals->getSubtotalCents()) }}</td>
        <td></td>
    </tr>
    @foreach($totals->getTaxGroups() as $taxGroup)
        <tr>
            <td colspan="3" class="invoice__totals-label">{{ __('invoices::invoice.tax_percentage', ['percentage' => $format->number($taxGroup->getPercentage(), 2), 'amount' => $format->money($taxGroup->getNetCents())]) }}</td>
            <td class="invoice__totals-value document__amount">{{ $format->money($taxGroup->getTaxCents()) }}</td>
            <td></td>
        </tr>
    @endforeach
    <tr>
        <td colspan="2"></td>
        <td class="invoice__totals-label invoice__totals-label--total">{{ __('invoices::invoice.total') }}</td>
        <td class="invoice__totals-value invoice__totals-value--total document__amount">{{ $format->money($totals->getTotalCents()) }}</td>
        <td></td>
    </tr>
</table>
