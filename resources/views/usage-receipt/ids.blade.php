{{-- document and user ids, only shown when the receipt has at least one --}}
@php($ids = array_filter([
    'document_id' => $usageReceipt->getDocumentId(),
    'user_id' => $usageReceipt->getUserId(),
], fn (?string $id): bool => $id !== null))

@if($ids !== [])
    <table class="usage-receipt__ids">
        <tr>
            @foreach($ids as $label => $id)
                <td class="usage-receipt__id-column document__text">
                    <div class="usage-receipt__id-label">{{ __('invoices::usage-receipt.'.$label) }}</div>
                    <div class="usage-receipt__id-value">{{ $id }}</div>
                </td>
            @endforeach
            {{-- pad to three columns, so the columns line up with the footer --}}
            @for($column = count($ids); $column < 3; $column++)
                <td class="usage-receipt__id-column"></td>
            @endfor
        </tr>
    </table>
@endif
