{{-- invoice items table --}}
<table class="invoice__items">
    <thead>
        <tr>
            <th width="5%" class="invoice__items-header"></th>
            <th width="45%" class="invoice__items-header">Omschrijving</th>
            <th width="15%" class="invoice__items-header invoice__items-header--right">Bedrag</th>
            <th width="15%" class="invoice__items-header invoice__items-header--right">Totaal</th>
            <th width="10%" class="invoice__items-header invoice__items-header--right">Btw</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoice->items as $item)
            <tr class="invoice__items-row">
                <td class="invoice__items-cell">{{ $item->quantity }}x</td>
                <td class="invoice__items-cell">
                    <span class="invoice__items-title">{{ $item->title }}</span>
                    @if(!empty($item->description))
                        <br><span class="invoice__items-description">{{ $item->description }}</span>
                    @endif
                </td>
                <td class="invoice__items-cell invoice__items-cell--right">€ {{ number_format($item->unit_price, 2, ',', '.') }}</td>
                <td class="invoice__items-cell invoice__items-cell--right">€ {{ number_format($item->total(), 2, ',', '.') }}</td>
                <td class="invoice__items-cell invoice__items-cell--right">{{ $item->tax_percentage ?? '0' }}%</td>
            </tr>
        @endforeach
    </tbody>
</table>

{{-- invoice totals section --}}
<table class="invoice__totals">
    <tr>
        <td class="invoice__totals-cell">Subtotaal</td>
        <td width="35%" class="invoice__totals-cell">€ {{ number_format($invoice->subTotal(), 2, ',', '.') }}</td>
    </tr>
    
    {{-- loop through tax rates if you have multiple, otherwise single line --}}
    <tr>
        <td class="invoice__totals-cell">21% btw</td>
        <td class="invoice__totals-cell">€ {{ number_format($invoice->taxAmount(), 2, ',', '.') }}</td>
    </tr>

    <tr class="invoice__totals-row--final">
        <td class="invoice__totals-cell">Totaal</td>
        <td class="invoice__totals-cell">€ {{ number_format($invoice->total(), 2, ',', '.') }}</td>
    </tr>
</table>

<div style="clear: both;"></div>
