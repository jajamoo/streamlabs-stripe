<!DOCTYPE html>
<html>
<head>
    <title>Subscription Monthly Totals</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
</head>
<body>
<div class="container">
    <h1>Subscription Monthly Totals</h1>
    <table class="table table-striped">
        <thead>
        <tr>
            <th>Metric</th>
            @foreach($headers as $header)
                <th>{{ $header }}</th>
            @endforeach
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>Total Amount (USD)</td>
            @foreach($data as $monthData)
                <td>{{ $monthData['total_amount'] }}</td>
            @endforeach
        </tr>
        <tr>
            <td>Customer Emails</td>
            @foreach($data as $monthData)
                <td>{{ $monthData['emails'][0] }}</td>
            @endforeach
        </tr>
        </tbody>
    </table>
</div>
</body>
</html>