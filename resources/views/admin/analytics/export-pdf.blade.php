<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Analytics Summary Export</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #14202c;
        }
        .header {
            margin-bottom: 18px;
        }
        .title {
            font-size: 20px;
            font-weight: 700;
            margin: 0 0 6px;
        }
        .meta {
            margin: 2px 0;
            color: #4b5b63;
        }
        .section {
            margin-top: 20px;
        }
        .section-title {
            font-size: 14px;
            font-weight: 700;
            margin: 0 0 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #d6dfdb;
            padding: 8px;
            vertical-align: top;
            text-align: left;
        }
        th {
            background: #edf4ef;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="header">
        <p class="title">Analytics Summary Export</p>
        <p class="meta">Generated at: {{ $generatedAt }}</p>
        <p class="meta">Range: {{ $analytics['filters']['dateFrom'] }} to {{ $analytics['filters']['dateTo'] }}</p>
    </div>

    <div class="section">
        <p class="section-title">System Summary</p>
        <table>
            <tbody>
                <tr><th>Page Views</th><td>{{ $analytics['summary']['pageViews'] }}</td></tr>
                <tr><th>Searches</th><td>{{ $analytics['summary']['searches'] }}</td></tr>
                <tr><th>Active Users</th><td>{{ $analytics['summary']['activeUsers'] }}</td></tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <p class="section-title">Active vs Inactive Farmers by Barangay</p>
        <table>
            <thead>
                <tr><th>Barangay</th><th>Active</th><th>Inactive</th><th>Total</th></tr>
            </thead>
            <tbody>
                @foreach ($analytics['activeInactiveByBarangay'] as $row)
                    <tr>
                        <td>{{ $row['barangay'] }}</td>
                        <td>{{ $row['active'] }}</td>
                        <td>{{ $row['inactive'] }}</td>
                        <td>{{ $row['total'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <p class="section-title">Renewal Compliance Rate per Year</p>
        <table>
            <thead>
                <tr><th>Year</th><th>Eligible</th><th>Compliant</th><th>Rate</th></tr>
            </thead>
            <tbody>
                @foreach ($analytics['renewalCompliance'] as $row)
                    <tr>
                        <td>{{ $row['year'] }}</td>
                        <td>{{ $row['eligible'] }}</td>
                        <td>{{ $row['compliant'] }}</td>
                        <td>{{ $row['rate'] }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <p class="section-title">Application Approval and Rejection Trend</p>
        <table>
            <thead>
                <tr><th>Year</th><th>Approved</th><th>Rejected</th></tr>
            </thead>
            <tbody>
                @foreach ($analytics['applicationDecisionTrend'] as $row)
                    <tr>
                        <td>{{ $row['year'] }}</td>
                        <td>{{ $row['approved'] }}</td>
                        <td>{{ $row['rejected'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <p class="section-title">Most Common Inquiry Topics</p>
        <table>
            <thead>
                <tr><th>Topic</th><th>Total</th></tr>
            </thead>
            <tbody>
                @foreach ($analytics['inquiryTopics'] as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td>{{ $row['value'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <p class="section-title">Payment Collection Summary</p>
        <table>
            <tbody>
                <tr><th>Verified Payments</th><td>{{ $analytics['paymentCollectionSummary']['verifiedCount'] }}</td></tr>
                <tr><th>Average Payment</th><td>{{ number_format((float) $analytics['paymentCollectionSummary']['averagePayment'], 2) }}</td></tr>
                <tr><th>Latest Recorded Payment</th><td>{{ $analytics['paymentCollectionSummary']['latestPaidAt'] ?? 'No payments recorded' }}</td></tr>
            </tbody>
        </table>
        <table style="margin-top: 10px;">
            <thead>
                <tr><th>Method</th><th>Count</th><th>Amount</th></tr>
            </thead>
            <tbody>
                @foreach ($analytics['paymentCollectionSummary']['byMethod'] as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td>{{ $row['count'] }}</td>
                        <td>{{ number_format((float) $row['amount'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <p class="section-title">Mortuary Assistance Summary</p>
        <table>
            <tbody>
                <tr><th>Total Claims</th><td>{{ $analytics['mortuaryAssistanceSummary']['totalClaims'] }}</td></tr>
                <tr><th>Approved Claims</th><td>{{ $analytics['mortuaryAssistanceSummary']['approvedClaims'] }}</td></tr>
                <tr><th>Released Claims</th><td>{{ $analytics['mortuaryAssistanceSummary']['releasedClaims'] }}</td></tr>
                <tr><th>Pending Claims</th><td>{{ $analytics['mortuaryAssistanceSummary']['pendingClaims'] }}</td></tr>
                <tr><th>Rejected Claims</th><td>{{ $analytics['mortuaryAssistanceSummary']['rejectedClaims'] }}</td></tr>
                <tr><th>Total Assistance</th><td>{{ number_format((float) $analytics['mortuaryAssistanceSummary']['totalAmount'], 2) }}</td></tr>
                <tr><th>Average Claim</th><td>{{ number_format((float) $analytics['mortuaryAssistanceSummary']['averageAmount'], 2) }}</td></tr>
            </tbody>
        </table>
    </div>
</body>
</html>
