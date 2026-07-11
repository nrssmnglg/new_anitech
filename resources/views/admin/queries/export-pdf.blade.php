<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Farmer Inquiries Export</title>
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
        .muted {
            color: #5f6f67;
        }
    </style>
</head>
<body>
    <div class="header">
        <p class="title">Farmer Inquiries Export</p>
        <p class="meta">Generated at: {{ $generatedAt }}</p>
        <p class="meta">Status: {{ $filterLabels['status'] }}</p>
        <p class="meta">Year: {{ $filterLabels['year'] }}</p>
        <p class="meta">Barangay: {{ $filterLabels['barangay'] }}</p>
    </div>

    <table>
        <thead>
            <tr>
                @foreach ($selectedColumns as $label)
                    <th>{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($queries as $query)
                <tr>
                    @foreach (array_keys($selectedColumns) as $column)
                        <td>
                            @switch($column)
                                @case('farmer_code')
                                    {{ $query->farmer?->farmer_code ?? '' }}
                                    @break
                                @case('full_name')
                                    {{ $query->farmer?->full_name ?? 'Unknown Farmer' }}
                                    @break
                                @case('barangay')
                                    {{ $query->farmer?->barangay?->name ?? '' }}
                                    @break
                                @case('subject')
                                    {{ $query->subject }}
                                    @break
                                @case('message')
                                    {{ $query->message }}
                                    @break
                                @case('status')
                                    {{ $query->status }}
                                    @break
                                @case('responses_count')
                                    {{ $query->responses_count ?? 0 }}
                                    @break
                                @case('submitted_at')
                                    {{ optional($query->created_at)->format('M d, Y h:i A') }}
                                    @break
                                @default
                                    <span class="muted">-</span>
                            @endswitch
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($selectedColumns) }}">No farmer inquiries found for the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
