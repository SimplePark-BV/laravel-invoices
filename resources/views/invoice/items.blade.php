@php
    use SimpleParkBv\Invoices\Services\CurrencyFormatter;
@endphp

{{-- invoice items table --}}
<table class="invoice__items">
    <thead>
        <tr>
            <th width="5%" class="invoice__items-header"></th>
            <th width="45%" class="invoice__items-header">{{ __('invoices::invoice.description') }}</th>
            <th width="15%" class="invoice__items-header invoice__items-header--right">{{ __('invoices::invoice.amount') }}</th>
            <th width="15%" class="invoice__items-header invoice__items-header--right">{{ __('invoices::invoice.total') }}</th>
            <th width="10%" class="invoice__items-header invoice__items-header--right">{{ __('invoices::invoice.tax') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoice->getItems() as $item)
            <tr class="invoice__items-row">
                <td class="invoice__items-cell">{{ $item->getQuantity() }}x</td>
                <td class="invoice__items-cell">
                    <span class="invoice__items-title">{{ $item->getTitle() }}</span>
                    @if(!empty($item->getDescription()))
                        <br><span class="invoice__items-description">{{ $item->getDescription() }}</span>
                    @endif
                </td>
                <td class="invoice__items-cell invoice__items-cell--right">{{ CurrencyFormatter::format($item->getUnitPrice()) }}</td>
                <td class="invoice__items-cell invoice__items-cell--right">{{ CurrencyFormatter::format($item->getTotal()) }}</td>
                <td class="invoice__items-cell invoice__items-cell--right">{{ $item->getFormattedTaxPercentage() }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td class="invoice__items-cell-no-border invoice__items-cell"></td>
            <td class="invoice__items-cell-no-border invoice__items-cell"></td>
            <td class="invoice__totals-cell">
                <span>{{ __('invoices::invoice.subtotal') }}</span>
                @foreach($invoice->getTaxGroups() as $taxPercentage)
                    <span>{{ __('invoices::invoice.tax_percentage', ['percentage' => (int) $taxPercentage]) }}</span>
                @endforeach
            </td>
            <td class="invoice__totals-cell">
                <span>{{ CurrencyFormatter::format($invoice->getFormattedSubTotal()) }}</span>
                @foreach($invoice->getTaxGroups() as $taxPercentage)
                    <span>{{ CurrencyFormatter::format($invoice->getTaxAmountForTaxGroup($taxPercentage)) }}</span>
                @endforeach
            </td>
            <td class="invoice__items-cell-no-border invoice__items-cell"></td>
        </tr>
        <tr class="invoice__totals-row--final">
            <td class="invoice__items-cell-no-border invoice__items-cell"></td>
            <td class="invoice__items-cell-no-border invoice__items-cell"></td>
            <td class="invoice__totals-cell-border">{{ __('invoices::invoice.total') }}</td>
            <td class="invoice__totals-cell-border">{{ $invoice->getFormattedTotal() }}</td>
            <td class="invoice__items-cell-no-border invoice__items-cell"></td>
        </tr>
    </tfoot>
</table>
