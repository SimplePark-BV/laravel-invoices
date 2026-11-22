{{-- the ids and the details, three to a row, only shown when the receipt has at least one --}}
@php
    $fields = [];

    foreach (['document_id' => $usageReceipt->getDocumentId(), 'user_id' => $usageReceipt->getUserId()] as $key => $id) {
        if ($id !== null) {
            $fields[] = [__('invoices::usage-receipt.'.$key), $id];
        }
    }

    foreach ($usageReceipt->getDetails() as $label => $value) {
        $fields[] = [$label, $value];
    }
@endphp

@if($fields !== [])
    <table class="usage-receipt__ids">
        @foreach(array_chunk($fields, 3) as $row)
            <tr @class(['usage-receipt__ids-row--next' => ! $loop->first])>
                @foreach($row as [$label, $value])
                    <td class="usage-receipt__id-column document__text">
                        <div class="usage-receipt__id-label">{{ $label }}</div>
                        <div class="usage-receipt__id-value">{{ $value }}</div>
                    </td>
                @endforeach
                {{-- pad to three columns, so the columns line up with the footer --}}
                @for($column = count($row); $column < 3; $column++)
                    <td class="usage-receipt__id-column"></td>
                @endfor
            </tr>
        @endforeach
    </table>
@endif
