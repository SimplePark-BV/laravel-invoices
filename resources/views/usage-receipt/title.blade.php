{{-- usage receipt title and date section --}}
<table class="usage-receipt__title-section">
    <tr>
        <td class="usage-receipt__title-cell">
            <h1 class="usage-receipt__title">Parkeerbevestiging</h1>
        </td>
        <td class="usage-receipt__date-cell">
            <div class="usage-receipt__date-container">
                <div class="usage-receipt__date-label">Datum</div>
                <div>{{ $usageReceipt->getFormattedDate() }}</div>
            </div>
        </td>
    </tr>
</table>
