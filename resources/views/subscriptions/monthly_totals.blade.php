<!-- resources/views/subscription-invoices.blade.php -->

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">

    <title>Subscription Invoices</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }

        table, th, td {
            border: 1px solid black;
        }

        th, td {
            padding: 8px;
            text-align: left;
        }
    </style>
</head>
<body>
<h1>Subscription Invoices</h1>
<table tabindex="0" class="table table-striped">
    <thead>
    <tr>
        <th>Customer Email</th>
        <th>Product Name</th>
        @foreach ($nextMonths as $month)
            <th>{{ $month }}</th>
        @endforeach
        <th>Lifetime Time Value</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($customerInvoices as $invoice)
        <tr>
            <td>{{ $invoice['email'] }}</td>
            <td>{{ $invoice['product'] }}</td>
            @foreach ($nextMonths as $month)
                <td>${{ number_format($invoice['charges'][$month] ?? 0, 2) }}</td>
            @endforeach
            <td>${{ number_format($invoice['total'], 2) }}</td>
        </tr>
    @endforeach
    <tr>
        <td><strong>Totals</strong></td>
        <td></td>
        @foreach ($months as $month)
            <td><strong>${{ number_format($usdTotals[$month] ?? 0, 2) }}</strong></td>
        @endforeach
        <td><strong>${{ number_format(array_sum($usdTotals), 2) }}</strong></td>
    </tr>
    </tbody>
</table>
</body>
</html>
