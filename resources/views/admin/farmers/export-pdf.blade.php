<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 11px; margin: 24px; }
        h1 { margin: 0 0 6px; font-size: 20px; }
        .subtitle { margin: 0 0 16px; color: #4b5563; }
        .meta { margin-bottom: 16px; }
        .meta-row { margin-bottom: 4px; }
        .meta-label { font-weight: 700; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 7px; vertical-align: top; word-wrap: break-word; }
        th { background: #f3f4f6; font-size: 10px; text-transform: uppercase; }
        .empty { text-align: center; padding: 18px; }
    </style>
</head>
<body>
    <h1>Farmer Registry</h1>
    <p class="subtitle">Generated {{ $generatedAt }}</p>

    <div class="meta">
        <div class="meta-row"><span class="meta-label">Search:</span> {{ $filterLabels['search'] }}</div>
        <div class="meta-row"><span class="meta-label">Status:</span> {{ $filterLabels['status'] }}</div>
        <div class="meta-row"><span class="meta-label">Barangay:</span> {{ $filterLabels['barangay'] }}</div>
        <div class="meta-row"><span class="meta-label">Member Type:</span> {{ $filterLabels['member_type'] }}</div>
        <div class="meta-row"><span class="meta-label">Total Records:</span> {{ $farmers->count() }}</div>
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
            @forelse ($farmers as $farmer)
                <tr>
                    @foreach (array_keys($selectedColumns) as $column)
                        <td>
                            @switch($column)
                                @case('farmer_code'){{ $farmer->farmer_code }}@break
                                @case('full_name'){{ $farmer->full_name }}@break
                                @case('status'){{ $farmer->status->label() }}@break
                                @case('member_type'){{ $farmer->memberType?->code ? $farmer->memberType->code . ' - ' . $farmer->memberType->name : '' }}@break
                                @case('barangay'){{ $farmer->barangay?->name ?? '' }}@break
                                @case('association'){{ $farmer->association?->name ?? '' }}@break
                                @case('birth_date'){{ $farmer->profile?->birth_date?->format('Y-m-d') ?? '' }}@break
                                @case('mobile_number'){{ $farmer->profile?->mobile_number ?? '' }}@break
                                @case('address'){{ $farmer->profile?->address ?? '' }}@break
                                @case('registered_at'){{ optional($farmer->registered_at)->format('M d, Y') ?? '' }}@break
                            @endswitch
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($selectedColumns) }}" class="empty">No farmer records match the current filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
