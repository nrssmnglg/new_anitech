<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; }
        @page { margin: 22px 24px 34px; }
        body { margin: 0; font-family: DejaVu Sans, sans-serif; color: #26352f; font-size: 8px; line-height: 1.35; }
        .report-header { position: relative; width: 100%; height: 58px; margin-bottom: 8px; background: #00513f; }
        .brand-block { position: absolute; top: 0; right: 31%; bottom: 0; left: 0; color: #fff; padding: 11px 14px; }
        .brand-name { margin: 0; font-size: 16px; font-weight: 700; letter-spacing: .3px; }
        .report-name { margin-top: 2px; color: #d9eee7; font-size: 9px; text-transform: uppercase; letter-spacing: 1px; }
        .report-reference { position: absolute; top: 0; right: 0; bottom: 0; width: 31%; background: #edf4f0; padding: 7px 10px; text-align: right; color: #40534b; }
        .reference-label { color: #708079; font-size: 7px; font-weight: 700; text-transform: uppercase; letter-spacing: .7px; }
        .reference-value { margin-top: 2px; font-size: 8px; }
        .summary { width: 100%; min-height: 34px; margin-bottom: 8px; border: 1px solid #dce5e1; background: #f7f9f8; padding: 6px 8px; }
        .summary-line { margin-bottom: 3px; }
        .summary-line:last-child { margin-bottom: 0; }
        .summary-label { color: #708079; font-size: 6.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .45px; }
        .summary-value { color: #24362f; font-size: 7.5px; font-weight: 600; }
        .summary-separator { margin: 0 8px; color: #b1bdb8; }
        .record-count { color: #00513f; font-size: 9px; font-weight: 700; }
        .registry-table { width: 100%; border-collapse: collapse; table-layout: auto; }
        .registry-table th { border: 1px solid #17624f; background: #17624f; color: #fff; padding: 5px 5px; font-size: 7px; text-align: left; text-transform: uppercase; letter-spacing: .45px; }
        .registry-table td { border: 1px solid #dce4e0; padding: 5px; vertical-align: top; white-space: normal; }
        .registry-table tbody tr:nth-child(even) td { background: #f4f7f5; }
        .empty { text-align: center; padding: 18px !important; color: #68776f; }
        .breakdown { margin-top: 7px; border: 1px solid #d7e2dd; background: #f7f9f8; padding: 6px 8px; }
        .breakdown-item { color: #52635c; font-size: 7.5px; }
        .breakdown-code { color: #00513f; font-weight: 700; }
        .breakdown-total { color: #26352f; font-weight: 700; }
        .breakdown-separator { margin: 0 7px; color: #a8b6b0; }
        .footer { position: fixed; right: 0; bottom: -23px; left: 0; border-top: 1px solid #dce5e1; padding-top: 5px; color: #718079; font-size: 7px; }
        .footer-left { float: left; }
        .footer-right { float: right; }
        .page-number:after { content: "Page " counter(page); }
    </style>
</head>
<body>
    <div class="footer">
        <span class="footer-left">AniTech Farmer Registry &bull; Confidential administrative report</span>
        <span class="footer-right page-number"></span>
    </div>

    <div class="report-header">
        <div class="brand-block">
            <div class="brand-name">CITY AGRICULTURE OFFICE</div>
            <div class="report-name">AniTech Farmer Registry &bull; Official Farmer Registry Report</div>
        </div>
        <div class="report-reference">
            <div class="reference-label">Generated</div>
            <div class="reference-value">{{ $generatedAt }}</div>
            <div class="reference-label" style="margin-top: 4px;">Prepared by</div>
            <div class="reference-value">{{ $generatedBy }}</div>
        </div>
    </div>

    <div class="summary">
        <div class="summary-line">
            <span class="summary-label">Records:</span>
            <span class="record-count">{{ number_format($farmers->count()) }}</span>
            <span class="summary-separator">|</span>
            <span class="summary-label">Barangay:</span>
            <span class="summary-value">{{ $filterLabels['barangay'] }}</span>
            <span class="summary-separator">|</span>
            <span class="summary-label">Member Type:</span>
            <span class="summary-value">{{ $filterLabels['member_type'] }}</span>
        </div>
    </div>

    <table class="registry-table">
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
                                @case('gender'){{ $farmer->profile?->sex ? ucfirst(strtolower((string) $farmer->profile->sex)) : '' }}@break
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

    <div class="breakdown">
        @foreach ($memberTypeBreakdown as $memberType)
            <span class="breakdown-item"><span class="breakdown-code">{{ $memberType['code'] }}</span> &mdash; {{ $memberType['name'] }}: <span class="breakdown-total">{{ number_format($memberType['total']) }}</span></span>@if (! $loop->last)<span class="breakdown-separator">|</span>@endif
        @endforeach
    </div>
</body>
</html>
