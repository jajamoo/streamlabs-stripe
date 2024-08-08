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
        <th>Total</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td>{{ $customerEmail }}</td>
        <td>{{ $productName }}</td>
        @foreach ($nextMonths as $month)
            <td>${{ number_format($chargeData[$month] ?? 0, 2) }}</td>
        @endforeach
        <td>${{ number_format($totalAmount, 2) }}</td>
    </tr>
    </tbody>
</table>
</body>
</html>
