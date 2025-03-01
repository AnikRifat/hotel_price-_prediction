<!DOCTYPE html>
<html>
<head>
    <title>Price Projection Dashboard</title>
    <style>
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Price Projections (Feb 28, 2025 - Mar 29, 2025)</h1>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Room Type</th>
                <th>Projected Price (RM)</th>
                <th>Historical Avg Price (RM)</th>
                <th>Competitor Prices</th>
                <th>Avg Competitor Price (RM)</th>
                <th>Reason for Projected Price</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($projections as $date => $rooms)
                @foreach ($rooms as $roomType => $data)
                    <tr>
                        <td>{{ $date }}</td>
                        <td>{{ $roomType }}</td>
                        <td>{{ number_format($data['projected_price'], 2) }}</td>
                        <td>{{ number_format($data['avg_price'], 2) }}</td>
                        <td>
                            @foreach ($data['competitors'] as $comp)
                                {{ $comp['name'] }}: {{ number_format($comp['price'], 2) }}<br>
                            @endforeach
                        </td>
                        <td>{{ number_format($data['avg_competitor_price'], 2) }}</td>
                        <td>{{ $data['reason'] }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
</body>
</html>
