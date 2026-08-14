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
            background: #ffffff;
            color: #111827;
            font-family: "Times New Roman", Georgia, serif;
        }

        .report-shell {
            max-width: 210mm;
            margin: 0 auto;
            padding: 22px 18px 30px;
        }

        .report-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 14px;
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
            border: 2px solid #111827;
            padding: 18px 16px 20px;
            box-shadow: 0 16px 36px rgba(15, 23, 42, 0.08);
        }

        .report-title {
            margin: 0 0 12px;
            text-align: center;
            font-size: 18px;
            font-weight: 800;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th,
        td {
            border: 2px solid #111827;
            padding: 3px 4px;
            font-size: 10px;
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

        .subhead {
            font-size: 10px;
            line-height: 1.05;
        }

        .report-total td {
            font-weight: 700;
        }

        .report-footer {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(0, 0.8fr);
            gap: 12px;
            margin-top: 10px;
        }

        .report-summary,
        .report-notes {
            width: 100%;
        }

        .report-summary td,
        .report-notes td {
            font-size: 11px;
        }

        .report-notes td {
            vertical-align: top;
            line-height: 1.5;
        }

        @media print {
            @page {
                size: A4 portrait;
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

            .report-title {
                font-size: 15px;
            }

            th,
            td {
                border-width: 1px;
                padding: 2px 3px;
                font-size: 8px;
            }

            thead {
                display: table-header-group;
            }

            tr {
                break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="report-shell">
        <div class="report-toolbar">
            <div>
                <div>Generated {{ $generatedAt->format('F d, Y h:i A') }}</div>
                <div>Barangay summary report for renewal collections.</div>
            </div>
            <div class="report-toolbar__actions">
                <a href="{{ route('admin.renewals.index', array_filter(['section' => 'records'] + $recordFilters)) }}" class="report-btn">Back to Renewal Records</a>
                <button type="button" class="report-btn" onclick="window.print()">Print / Save PDF</button>
            </div>
        </div>

        <section class="report-sheet">
            <h1 class="report-title">SUMMARY OF BASACAFEFA RENEWAL CY {{ $reportYear }}</h1>

            @php
                $visibleColumns = array_keys($selectedColumns);
                $columnWeights = [
                    'barangay' => 45,
                    'farmer_count' => 19,
                    'annual_due' => 21,
                    'mortuary_fee' => 23,
                    'membership_fee' => 25,
                    'total_amount' => 23,
                    'membership_count' => 17,
                    'without_mortuary_count' => 17,
                    'female_count' => 17,
                    'male_count' => 17,
                ];
                $headerLabels = [
                    'barangay' => 'BARANGAY',
                    'farmer_count' => 'NO. OF FARMERS',
                    'annual_due' => 'ANNUAL DUES',
                    'mortuary_fee' => 'MORTUARY',
                    'membership_fee' => 'MEMBERSHIP (NEW)',
                    'total_amount' => 'TOTAL AMOUNT',
                    'membership_count' => 'NM',
                    'without_mortuary_count' => 'W/O M',
                    'female_count' => 'FEMALE',
                    'male_count' => 'MALE',
                ];
                $moneyColumns = ['annual_due', 'mortuary_fee', 'membership_fee', 'total_amount'];
                $totalWeight = collect($visibleColumns)->sum(fn (string $column): int => $columnWeights[$column] ?? 1);
                $showRemarksGroup = in_array('membership_count', $visibleColumns, true)
                    && in_array('without_mortuary_count', $visibleColumns, true);
            @endphp

            <table>
                <colgroup>
                    @foreach ($visibleColumns as $column)
                        <col style="width: {{ number_format((($columnWeights[$column] ?? 1) / max($totalWeight, 1)) * 100, 4, '.', '') }}%">
                    @endforeach
                </colgroup>
                <thead>
                    <tr>
                        @foreach ($visibleColumns as $column)
                            @if ($showRemarksGroup && $column === 'without_mortuary_count')
                                @continue
                            @endif

                            @if ($showRemarksGroup && $column === 'membership_count')
                                <th colspan="2">REMARKS</th>
                            @else
                                <th rowspan="{{ $showRemarksGroup ? 2 : 1 }}">{{ $headerLabels[$column] ?? strtoupper($selectedColumns[$column]) }}</th>
                            @endif
                        @endforeach
                    </tr>
                    @if ($showRemarksGroup)
                        <tr>
                            <th class="subhead">NM</th>
                            <th class="subhead">W/O M</th>
                        </tr>
                    @endif
                </thead>
                <tbody>
                    @forelse ($summaryRows as $row)
                        <tr>
                            @foreach ($visibleColumns as $column)
                                <td class="{{ $column === 'barangay' ? 'text-left' : (in_array($column, $moneyColumns, true) ? 'text-right' : 'text-center') }}">
                                    @if ($column === 'barangay')
                                        {{ strtoupper((string) ($row[$column] ?? '')) }}
                                    @elseif (in_array($column, $moneyColumns, true))
                                        {{ number_format((float) ($row[$column] ?? 0), 0) }}
                                    @else
                                        {{ number_format((int) ($row[$column] ?? 0)) }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($visibleColumns) }}" class="text-center">No renewal records matched the current filters.</td>
                        </tr>
                    @endforelse

                    <tr class="report-total">
                        @foreach ($visibleColumns as $column)
                            @php
                                $totalKey = $column === 'farmer_count' ? 'farmers' : $column;
                            @endphp
                            <td class="{{ $column === 'barangay' ? 'text-center' : (in_array($column, $moneyColumns, true) ? 'text-right' : 'text-center') }}">
                                @if ($column === 'barangay')
                                    TOTAL
                                @elseif (in_array($column, $moneyColumns, true))
                                    {{ number_format((float) ($totals[$totalKey] ?? 0), 0) }}
                                @else
                                    {{ number_format((int) ($totals[$totalKey] ?? 0)) }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                </tbody>
            </table>

            <div class="report-footer">
                <table class="report-summary">
                    <tbody>
                        <tr>
                            <th>Members</th>
                            <th>With Mortuary</th>
                            <th>Without Mortuary</th>
                            <th>Total</th>
                        </tr>
                        <tr>
                            <td>New: {{ number_format((int) ($totals['membership_count'] ?? 0)) }}</td>
                            <td class="text-center">{{ number_format((int) ($totals['with_mortuary_count'] ?? 0)) }}</td>
                            <td class="text-center">{{ number_format((int) ($totals['without_mortuary_count'] ?? 0)) }}</td>
                            <td class="text-center">{{ number_format((int) ($totals['total_member_count'] ?? 0)) }}</td>
                        </tr>
                        <tr>
                            <td>Old: {{ number_format((int) ($totals['old_member_count'] ?? 0)) }}</td>
                            <td colspan="3"></td>
                        </tr>
                    </tbody>
                </table>

                <table class="report-notes">
                    <tbody>
                        <tr>
                            <th>Remarks</th>
                        </tr>
                        <tr>
                            <td>
                                NM - New Member<br>
                                W/O M - Without Mortuary
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</body>
</html>
