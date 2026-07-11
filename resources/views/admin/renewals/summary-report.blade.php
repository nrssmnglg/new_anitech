<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Renewal Summary Report</title>
    <style>
        :root {
            color-scheme: light;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f3efe6;
            color: #111827;
            font-family: "Times New Roman", Georgia, serif;
        }

        .report-shell {
            max-width: 980px;
            margin: 0 auto;
            padding: 28px 20px 36px;
        }

        .report-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 14px;
        }

        .report-toolbar__meta {
            font: 600 12px/1.5 Arial, Helvetica, sans-serif;
            color: #475569;
        }

        .report-toolbar__actions {
            display: flex;
            gap: 10px;
        }

        .report-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #111827;
            background: #ffffff;
            color: #111827;
            padding: 9px 14px;
            text-decoration: none;
            font: 600 12px/1 Arial, Helvetica, sans-serif;
            cursor: pointer;
        }

        .report-sheet {
            background: #fffdfa;
            border: 1px solid #111827;
            padding: 18px 16px 20px;
            box-shadow: 0 16px 36px rgba(15, 23, 42, 0.08);
        }

        .report-title {
            margin: 0 0 10px;
            text-align: center;
            font-size: 24px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .report-subtitle {
            margin: 0 0 16px;
            text-align: center;
            font: 600 12px/1.4 Arial, Helvetica, sans-serif;
            color: #475569;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #111827;
            padding: 5px 6px;
            font-size: 12px;
            vertical-align: middle;
        }

        th {
            text-align: center;
            font-weight: 700;
        }

        td.text-left {
            text-align: left;
        }

        td.text-center {
            text-align: center;
        }

        td.text-right {
            text-align: right;
        }

        .col-barangay {
            width: 16%;
        }

        .col-farmers {
            width: 10%;
        }

        .col-annual,
        .col-mortuary,
        .col-membership,
        .col-total {
            width: 12%;
        }

        .col-nm,
        .col-without,
        .col-female,
        .col-male {
            width: 7.5%;
        }

        .report-total td {
            font-weight: 700;
        }

        .report-empty {
            padding: 20px 12px;
            text-align: center;
            font: 600 13px/1.5 Arial, Helvetica, sans-serif;
        }

        @media print {
            @page {
                size: landscape;
                margin: 10mm;
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
        }
    </style>
</head>
<body>
    <div class="report-shell">
        <div class="report-toolbar">
            <div class="report-toolbar__meta">
                <div>Generated {{ $generatedAt->format('F d, Y h:i A') }}</div>
                <div>Association: {{ strtoupper($selectedAssociation?->name ?? 'ALL ASSOCIATIONS') }}</div>
            </div>
            <div class="report-toolbar__actions">
                <a href="{{ route('admin.renewals.index', array_filter(['section' => 'records'] + $recordFilters)) }}" class="report-btn">Back to Renewal Records</a>
                <button type="button" class="report-btn" onclick="window.print()">Print / Save PDF</button>
            </div>
        </div>

        <section class="report-sheet">
            <h1 class="report-title">Summary of {{ strtoupper($selectedAssociation?->name ?? 'All Associations') }} Renewal CY {{ $reportYear }}</h1>

            <table>
                <thead>
                    <tr>
                        @foreach ($selectedColumns as $label)
                            <th>{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($summaryRows as $row)
                        <tr>
                            @foreach (array_keys($selectedColumns) as $column)
                                <td class="{{ $column === 'barangay' ? 'text-left' : (in_array($column, ['farmer_count', 'membership_count', 'without_mortuary_count', 'female_count', 'male_count'], true) ? 'text-center' : 'text-right') }}">
                                    {{ $column === 'barangay' ? strtoupper((string) $row[$column]) : number_format((float) $row[$column], 0) }}
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($selectedColumns) }}" class="report-empty">No renewal records matched the current filters.</td>
                        </tr>
                    @endforelse
                    @if ($summaryRows->isNotEmpty())
                        <tr class="report-total">
                            @foreach (array_keys($selectedColumns) as $column)
                                <td class="{{ $column === 'barangay' ? 'text-left' : (in_array($column, ['farmer_count', 'membership_count', 'without_mortuary_count', 'female_count', 'male_count'], true) ? 'text-center' : 'text-right') }}">
                                    {{
                                        $column === 'barangay'
                                            ? 'TOTAL'
                                            : number_format((float) match ($column) {
                                                'farmer_count' => $totals['farmers'],
                                                'annual_due' => $totals['annual_due'],
                                                'mortuary_fee' => $totals['mortuary_fee'],
                                                'membership_fee' => $totals['membership_fee'],
                                                'total_amount' => $totals['total_amount'],
                                                'membership_count' => $totals['membership_count'],
                                                'without_mortuary_count' => $totals['without_mortuary_count'],
                                                'female_count' => $totals['female_count'],
                                                'male_count' => $totals['male_count'],
                                                default => 0,
                                            }, 0)
                                    }}
                                </td>
                            @endforeach
                        </tr>
                    @endif
                </tbody>
            </table>
        </section>
    </div>
</body>
</html>
