<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <title>Factuur {{ $invoice->number ?? 'Concept' }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 14px;
            line-height: 1.4;
            color: #333;
        }

        /* layout helpers */
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        /* spacing matches the visual gaps in your image */
        .mb-2 { margin-bottom: 10px; }
        .mb-4 { margin-bottom: 20px; }
        .mb-6 { margin-bottom: 40px; }
        
        /* typography */
        h1 {
            font-size: 24px;
            font-weight: bold;
            color: #1a202c; /* dark navy similar to image */
            margin: 0;
        }

        .text-sm { font-size: 12px; }
        .text-gray { color: #6b7280; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        
        /* specific colors from your brand */
        .text-brand { color: #2563eb; }

        /* header section */
        .header-table td {
            vertical-align: top;
        }
        .seller-info {
            line-height: 1.6;
        }

        /* invoice items table */
        .items-table {
            width: 100%;
            margin-top: 30px;
        }

        .items-table th {
            text-align: left;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
            font-weight: bold;
        }

        .items-table td {
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6; /* very light gray divider */
            vertical-align: top;
        }
        
        /* last row shouldn't have a border usually, but in your design it looks clean without or with */
        .items-table tr:last-child td {
            border-bottom: none;
        }

        /* totals section */
        .totals-table {
            width: 40%;
            float: right; 
            margin-top: 20px;
        }
        .totals-table td {
            padding: 5px 0;
            text-align: right;
        }
        .totals-table .total-final {
            font-weight: bold;
            font-size: 16px;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
            margin-top: 5px;
        }

        /* footer */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 50px;
            font-size: 13px;
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
        }
        
        /* pagebBreak prevention */
        tr { page-break-inside: avoid; }
    </style>
</head>
<body>
    <table class="header-table mb-6">
        <tr>
            <td width="50%">
                @if($invoice->getLogoDataUri())
                    <img src="{{ $invoice->getLogoDataUri() }}" alt="Logo" style="height: 50px; width: auto;">
                @else
                    <h2 class="text-brand">{{ $invoice->seller->name }}</h2>
                @endif
            </td>

            <td width="50%" class="text-right text-sm seller-info">
                <strong>{{ $invoice->seller->name }}</strong><br>
                {{ $invoice->seller->address }}<br>
                {{ $invoice->seller->postal_code }} {{ $invoice->seller->city }}<br>
                <br>
                {{ $invoice->seller->email }}<br>
                <br>
                KVK: {{ $invoice->seller->kvk ?? 'Unknown' }}<br>
                Btw: {{ $invoice->seller->btw ?? 'Unknown' }}<br>
                Bank: {{ $invoice->seller->iban ?? 'Unknown' }}
            </td>
        </tr>
    </table>

    <div class="mb-6 text-sm" style="margin-top: -20px;">
        <strong>{{ $invoice->buyer->name }}</strong><br>
        {{ $invoice->buyer->address }}<br>
        {{ $invoice->buyer->postal_code }} {{ $invoice->buyer->city }}<br>
        <br>
        {{ $invoice->buyer->email }}<br>
        {{ $invoice->buyer->phone }}
    </div>

    <table class="mb-4">
        <tr>
            <td width="60%">
                <h1>Factuur {{ $invoice->number ?? 'CONCEPT' }}</h1>
            </td>
            <td width="40%" class="text-right text-sm">
                <table style="width: auto; float: right;">
                    <tr>
                        <td class="text-right" style="padding-right: 20px;">Factuurdatum:</td>
                        <td class="text-right">{{ $invoice->date->format('d-m-Y') }}</td>
                    </tr>
                    <tr>
                        <td class="text-right" style="padding-right: 20px;">Vervaldatum:</td>
                        <td class="text-right">{{ $invoice->date->addDays($invoice->pay_until_days)->format('d-m-Y') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th width="5%"></th> <th width="45%">Omschrijving</th>
                <th width="15%" class="text-right">Bedrag</th>
                <th width="15%" class="text-right">Totaal</th>
                <th width="10%" class="text-right">Btw</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->quantity }}x</td>
                    <td>
                        <strong>{{ $item->description }}</strong>
                        @if(!empty($item->sub_description))
                            <br><span class="text-gray text-sm">{{ $item->sub_description }}</span>
                        @endif
                    </td>
                    <td class="text-right">€ {{ number_format($item->unit_price, 2, ',', '.') }}</td>
                    <td class="text-right">€ {{ number_format($item->total(), 2, ',', '.') }}</td>
                    <td class="text-right">{{ $item->tax_percentage ?? '0' }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td>Subtotaal</td>
            <td width="35%">€ {{ number_format($invoice->subTotal(), 2, ',', '.') }}</td>
        </tr>
        
        {{-- loop through tax rates if you have multiple, otherwise single line --}}
        <tr>
            <td>21% btw</td>
            <td>€ {{ number_format($invoice->taxAmount(), 2, ',', '.') }}</td>
        </tr>

        <tr class="total-final">
            <td>Totaal</td>
            <td>€ {{ number_format($invoice->total(), 2, ',', '.') }}</td>
        </tr>
    </table>

    <div style="clear: both;"></div>

    <div class="footer text-sm">
        We verzoeken je vriendelijk het bovenstaande bedrag van 
        <strong>€ {{ number_format($invoice->total(), 2, ',', '.') }}</strong> 
        voor <strong>{{ $invoice->date->addDays($invoice->pay_until_days)->format('d-m-Y') }}</strong> 
        te voldoen op onze bankrekening. Voor vragen kan je contact opnemen per e-mail.
    </div>
</body>
</html>