<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Farmer Member Information Report</title>
    <style>
        :root {
            color-scheme: light;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f4f1e8;
            color: #1f2937;
            font-family: "Times New Roman", Georgia, serif;
        }

        .report-shell {
            max-width: 1320px;
            margin: 0 auto;
            padding: 32px 24px 40px;
        }

        .report-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
        }

        .report-toolbar__meta {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            line-height: 1.5;
            color: #475569;
        }

        .report-toolbar__actions {
            display: flex;
            gap: 12px;
        }

        .report-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #1f2937;
            background: #ffffff;
            color: #111827;
            padding: 10px 16px;
            font: 600 13px/1 Arial, Helvetica, sans-serif;
            text-decoration: none;
            cursor: pointer;
        }

        .report-sheet {
            background: #fffdf8;
            border: 1px solid #1f2937;
            padding: 20px 18px 22px;
            box-shadow: 0 16px 36px rgba(15, 23, 42, 0.08);
        }

        .report-header {
            text-align: center;
            margin-bottom: 12px;
        }

        .report-header h1 {
            margin: 0;
            font-size: 22px;
            line-height: 1.2;
            font-weight: 700;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .report-header p {
            margin: 8px 0 0;
            font: 600 12px/1.4 Arial, Helvetica, sans-serif;
            color: #475569;
        }

        .report-filter-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
            margin-bottom: 12px;
            font: 600 11px/1.35 Arial, Helvetica, sans-serif;
        }

        .report-filter-chip {
            border: 1px solid #1f2937;
            padding: 6px 8px;
            min-height: 42px;
        }

        .report-filter-chip strong {
            display: block;
            margin-bottom: 4px;
            font-size: 10px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #1f2937;
            padding: 6px 7px;
            vertical-align: middle;
        }

        th {
            text-align: center;
            font-size: 13px;
            font-weight: 700;
        }

        td {
            font-size: 12px;
            height: 28px;
        }

        .col-no {
            width: 5%;
            text-align: center;
        }

        .col-name {
            width: 38%;
        }

        .col-address {
            width: 26%;
        }

        .col-dob {
            width: 14%;
            text-align: center;
        }

        .col-contact {
            width: 17%;
        }

        .report-empty {
            padding: 18px 12px;
            text-align: center;
            font: 600 13px/1.5 Arial, Helvetica, sans-serif;
        }

        @media print {
            @page {
                size: landscape;
                margin: 12mm;
            }

            body {
                background: #ffffff;
            }

            .report-shell {
                max-width: none;
                margin: 0;
                padding: 0;
            }

            .report-toolbar {
                display: none;
            }

            .report-sheet {
                border: 0;
                padding: 0;
                box-shadow: none;
            }

            .report-filter-chip {
                min-height: 36px;
            }
        }
    </style>
</head>
<body>
    <div class="report-shell">
        <div class="report-toolbar">
            <div class="report-toolbar__meta">
                <div>Generated {{ $generatedAt->format('F d, Y h:i A') }}</div>
                <div>Rows: {{ number_format($farmers->count()) }}</div>
            </div>
            <div class="report-toolbar__actions">
                <a href="{{ route('admin.farmers.index', array_filter($filters)) }}" class="report-btn">Back to Farmer List</a>
                <button type="button" class="report-btn" onclick="window.print()">Print / Save PDF</button>
            </div>
        </div>

        <section class="report-sheet">
            @php($associationNames = $farmers->pluck('association.name')->filter()->unique()->values())
            <header class="report-header">
                <h1>Farmer Member Information</h1>
                <p>AniTech Agriculture System Registry Report</p>
                @if (filled($filters['barangay_id']))
                    <p>Barangay: {{ $filterLabels['barangay'] }}</p>
                    <p>Association: {{ $associationNames->isNotEmpty() ? $associationNames->implode(', ') : 'No association recorded' }}</p>
                @endif
            </header>

            <table>
                <thead>
                    <tr>
                        <th class="col-no">No.</th>
                        <th class="col-name">Name of member</th>
                        <th class="col-address">Address</th>
                        <th class="col-dob">Date of Birth</th>
                        <th class="col-contact">Contact no.</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($farmers as $index => $farmer)
                        <tr>
                            <td class="col-no">{{ $index + 1 }}</td>
                            <td class="col-name">{{ $farmer->full_name }}</td>
                            <td class="col-address">{{ $farmer->profile?->address ?: trim(collect([$farmer->association?->name, $farmer->barangay?->name])->filter()->implode(', ')) }}</td>
                            <td class="col-dob">{{ $farmer->profile?->birth_date?->format('m/d/Y') ?? '' }}</td>
                            <td class="col-contact">{{ $farmer->profile?->mobile_number ?: '' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="report-empty">No farmer records match the current filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>
</body>
</html>
