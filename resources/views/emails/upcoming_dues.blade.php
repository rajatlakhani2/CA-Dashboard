<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: sans-serif;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #777;
        }
    </style>
</head>

<body>
    <h2>Upcoming Compliance Dues</h2>
    <p>Hello Admin,</p>
    <p>Here is the list of compliances due in the next 7 days:</p>

    <table>
        <thead>
            <tr>
                <th>Due Date</th>
                <th>Client</th>
                <th>Service</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dues as $due)
            <tr>
                <td>{{ $due->due_date->format('d M Y') }}</td>
                <td>{{ $due->clientService->client->name }}</td>
                <td>{{ $due->clientService->service->name }}</td>
                <td>{{ $due->status }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>This is an automated message from your RLA Dashboard.</p>
    </div>
</body>

</html>