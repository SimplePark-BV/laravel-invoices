<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice</title>
</head>
<body>
    @include('invoices::invoice.header')
    
    @include('invoices::invoice.seller')
    
    @include('invoices::invoice.buyer')
    
    @include('invoices::invoice.items')
    
    @include('invoices::invoice.totals')
    
    @include('invoices::invoice.footer')
</body>
</html>

