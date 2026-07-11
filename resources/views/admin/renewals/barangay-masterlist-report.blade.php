<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Barangay Renewal Masterlist</title>
    <style>
        :root {
            color-scheme: light;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f4f1e7;
            color: #111827;
            font-family: "Times New Roman", Georgia, serif;
        }

        .report-shell {
            max-width: 980px;
            margin: 0 auto;
            padding: 24px 18px 32px;
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
            padding: 16px 14px 18px;
            box-shadow: 0 16px 36px rgba(15, 23, 42, 0.08);
        }

        .report-title {
            margin: 0 0 12px;
            text-align: center;
            font-size: 22px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .report-header-grid {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 8px 18px;
            margin-bottom: 10px;
            font-size: 13px;
        }

        .report-header-grid p {
            margin: 0;
        }

        .report-line {
            display: inline-block;
            min-width: 220px;
            border-bottom: 1px solid #111827;
            padding: 0 4px 2px;
            font-weight: 700;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #111827;
            padding: 4px 6px;
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

        .col-name {
            width: 34%;
        }

        .col-amount {
            width: 13%;
        }

        .col-remarks {
            width: 14%;
        }

        .blank-row td {
            height: 23px;
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
                size: portrait;
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
                <div>Barangay report format selected because a barangay filter is active.</div>
            </div>
            <div class="report-toolbar__actions">
                <a href="{{ route('admin.renewals.index', array_filter(['section' => 'records'] + $recordFilters)) }}" class="report-btn">Back to Renewal Records</a>
                <button type="button" class="report-btn" onclick="window.print()">Print / Save PDF</button>
            </div>
        </div>

        <section class="report-sheet">
            <h1 class="report-title">MASTERLIST OF RENEWAL AND MEMBERSHIP</h1>

            <div class="report-header-grid">
                <p>Barangay: <span class="report-line">{{ strtoupper($selectedBarangay?->name ?? 'N/A') }}</span></p>
                <p>For the Year: <span class="report-line" style="min-width: 90px; text-align: center;">{{ $reportYear }}</span></p>
                <p>Name of Farmer Association: <span class="report-line">{{ strtoupper($selectedAssociation?->name ?? 'NO ASSOCIATION RECORDED') }}</span></p>
                <p></p>
                <p></p>
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
                    @forelse ($masterlistRows as $row)
                        <tr>
                            @foreach (array_keys($selectedColumns) as $column)
                                <td class="{{ $column === 'name' ? 'text-left' : ($column === 'remarks' ? 'text-center' : 'text-right') }}">
                                    {{
                                        $column === 'name'
                                            ? strtoupper((string) $row[$column])
                                            : ($column === 'remarks' ? $row[$column] : number_format((float) $row[$column], 2))
                                    }}
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr class="blank-row">
                            <td colspan="{{ count($selectedColumns) }}" class="text-center">No renewal records matched the current filters.</td>
                        </tr>
                    @endforelse
                    @for ($i = $masterlistRows->count(); $i < 28; $i++)
                        <tr class="blank-row">
                            @for ($cell = 0; $cell < count($selectedColumns); $cell++)
                                <td></td>
                            @endfor
                        </tr>
                    @endfor
                    <tr class="report-total">
                        @foreach (array_keys($selectedColumns) as $column)
                            <td class="{{ $column === 'name' ? 'text-center' : ($column === 'remarks' ? 'text-center' : 'text-right') }}">
                                {{
                                    $column === 'name'
                                        ? 'TOTAL'
                                        : ($column === 'annual_due' ? 'PHP ' . number_format($totals['annual_due'], 2)
                                        : ($column === 'mortuary_fee' ? 'PHP ' . number_format($totals['mortuary_fee'], 2)
                                        : ($column === 'membership_fee' ? 'PHP ' . number_format($totals['membership_fee'], 2)
                                        : ($column === 'total_amount' ? 'PHP ' . number_format($totals['total_amount'], 2) : ''))))
                                }}
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
                            <td>New: {{ number_format($totals['new_member_count']) }}</td>
                            <td class="text-center">{{ number_format($totals['with_mortuary_count']) }}</td>
                            <td class="text-center">{{ number_format($totals['without_mortuary_count']) }}</td>
                            <td class="text-center">{{ number_format($totals['total_member_count']) }}</td>
                        </tr>
                        <tr>
                            <td>Old: {{ number_format($totals['old_member_count']) }}</td>
                            <td colspan="3"></td>
                        </tr>
                        <tr>
                            <td>Total no. of members: {{ number_format($totals['total_member_count']) }}</td>
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
                                Please indicate if:<br>
                                OM - Old Member<br>
                                NM - New Member<br>
                                OSC - Old Senior Citizen Member<br>
                                NSC - New Senior Citizen Member
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</body>
</html>
