<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{$invoice_number}}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .invoice-logo {
            max-height: 60px;
        }
        .invoice-info {
            text-align: right;
        }
        .addresses {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .address {
            white-space: pre-line;
            line-height: 3px;
        }
        .address-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .invoice-items {
            width: 100%;
            border-collapse: collapse;
        }
        .invoice-items th,
        .invoice-items td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        .invoice-items th {
            background-color: #f2f2f2;
            font-weight: bold;
            color: #0000ff;
        }
        .totals {
            text-align: right;
            margin-top: 20px;
        }
    </style>
</head>
<body>
<div class="invoice-header">
    <img src="https://cdn.bagou450.com/assets/img/logo_full_colored.webp" alt="Logo" class="invoice-logo">
    <div class="invoice-info">
        <p>Invoice ID: {{$invoice_number}}</p>
        <p>Invoice date: {{$invoice_date}}</p>
        <p>Due date: {{$due_date}}</p>
    </div>
</div>
<div class="addresses">
    <div class="address">
        <p class="address-title">Company Address</p>
        <p>Bagou450 SARL</p>
        <p>02 rue des orchidées</p>
        <p>35450, Dourdain</p>
        <p>Bretagne, France</p>
        <p>contact@bagou450.com</p>
        <p>SIRET: 12345678901234</p> 
    </div>
    <div class="address" style="text-align: right;">
        <p class="address-title">Billing Address</p>
        <p>{{$customer['name']}}</p>
        <p>{{$customer['address']}}</p>
        <p>{{$customer['postal_code']}}, {{$customer['city']}}</p>
        <p>{{$customer['region']}}, {{$customer['country']}}</p>
        <p>{{$customer['email']}}</p>
    </div>
</div>

<table class="invoice-items">
    <thead>
        <tr>
            <th>Description</th>
            <th>Quantity</th>
            <th>Unit price</th>
            <th>Fees</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $item)
            <tr>
                <td>{{$item['description']}}</td>
                <td>{{$item['quantity']}}</td>
                <td>{{$item['price']}}€</td>
                <td>{{ $item['price'] == 0 ? '0€' : number_format(0.35, 2).'€' }}</td>
                <td>{{ $item['price'] == 0 ? $item['price'].'€' : number_format($item['price'] + 0.35, 2).'€' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
<div class="totals">
    <p>Total excl. Fees: {{number_format(array_sum(array_column($items, 'price')), 2)}}€</p>
    <p>Total Fees: {{number_format(count($items) * 0.35, 2)}}€</p>
    <p>Total incl. Fees: {{number_format(array_sum(array_map(function($item) { return $item['price'] + 0.35; }, $items)), 2)}}€</p>
</div>
</body>
</html>