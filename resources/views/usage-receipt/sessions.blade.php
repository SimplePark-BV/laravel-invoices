{{-- parking sessions table --}}
<table class="usage-receipt__sessions">
    <thead>
        <tr>
            <th class="usage-receipt__sessions-header">Gebruiker</th>
            <th class="usage-receipt__sessions-header">Kenteken</th>
            <th class="usage-receipt__sessions-header">Startdatum</th>
            <th class="usage-receipt__sessions-header">Einddatum</th>
            <th class="usage-receipt__sessions-header">Zone</th>
            <th class="usage-receipt__sessions-header usage-receipt__sessions-header--right">Prijs</th>
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
        <td class="usage-receipt__total-label">Totaal</td>
        <td class="usage-receipt__total-value">{{ $usageReceipt->getFormattedTotal() }}</td>
    </tr>
</table>
