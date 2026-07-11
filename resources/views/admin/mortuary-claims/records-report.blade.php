<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mortuary Records</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1a2420; font-size: 12px; }
        h1 { margin: 0 0 6px; font-size: 22px; }
        p { margin: 0 0 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        th, td { border: 1px solid #d7e0db; padding: 8px 10px; text-align: left; vertical-align: top; }
        th { background: #f4f7f5; font-size: 11px; text-transform: uppercase; }
        .meta { margin-bottom: 18px; color: #52615a; }
        .empty { text-align: center; padding: 18px; color: #697772; }
    </style>
</head>
<body>
    <h1>Mortuary Records</h1>
    <div class="meta">
        <p>Generated: {{ $generatedAt->format('F d, Y h:i A') }}</p>
        <p>Search: {{ data_get($filters, 'search') ?: 'All claims' }}</p>
        <p>Status: {{ data_get($filters, 'status') ?: 'All statuses' }}</p>
        <p>Year: {{ data_get($filters, 'year') ?: 'All years' }}</p>
        <p>Barangay: {{ $selectedBarangay?->name ?? 'All barangays' }}</p>
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
            @forelse ($rows as $row)
                <tr>
                    @foreach (array_keys($selectedColumns) as $column)
                        <td>{{ $row[$column] ?? '' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($selectedColumns) }}" class="empty">No mortuary records matched the current filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
