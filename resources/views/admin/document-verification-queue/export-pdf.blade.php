<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Document Verification Queue Export</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #14202c;
        }
        .header {
            margin-bottom: 18px;
        }
        .title {
            font-size: 18px;
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
            padding: 7px;
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
        <p class="title">Document Verification Queue Export</p>
        <p class="meta">Generated at: {{ $generatedAt }}</p>
        <p class="meta">Filters: Search "{{ $filters['search'] ?: 'All' }}", Workflow "{{ ucfirst($filters['workflow']) }}", Status "{{ ucfirst($filters['status']) }}", Flag "{{ ucfirst($filters['flag']) }}"</p>
    </div>

    <table>
        <thead>
            <tr>
                @foreach ($columns as $column)
                    <th>{{ $columnLabels[$column] ?? $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($documents as $document)
                <tr>
                    @foreach ($columns as $column)
                        <td>
                            @switch($column)
                                @case('workflow')
                                    {{ $document['workflowLabel'] }}
                                    @break
                                @case('farmer_name')
                                    {{ $document['farmerName'] }}
                                    @break
                                @case('farmer_code')
                                    {{ $document['farmerCode'] }}
                                    @break
                                @case('document_label')
                                    {{ $document['documentLabel'] }}
                                    @break
                                @case('reference_label')
                                    {{ $document['referenceLabel'] }}
                                    @break
                                @case('source_label')
                                    {{ $document['sourceLabel'] }}
                                    @break
                                @case('status')
                                    {{ $document['status']['label'] }}
                                    @break
                                @case('flag')
                                    @if ($document['needsResubmission'])
                                        Re-submission
                                    @elseif ($document['isExpired'])
                                        Expired
                                    @elseif (! $document['uploadPresent'])
                                        Missing
                                    @elseif ($document['readyForVerification'])
                                        Ready
                                    @else
                                        Waiting
                                    @endif
                                    @break
                                @case('uploaded_at')
                                    {{ $document['uploadedAt'] ?? '' }}
                                    @break
                                @case('verified_at')
                                    {{ $document['verifiedAt'] ?? '' }}
                                    @break
                                @case('expires_at')
                                    {{ $document['expiresAtLabel'] ?? '' }}
                                    @break
                                @case('verifier_name')
                                    {{ $document['verifierName'] ?? '' }}
                                    @break
                                @case('remarks')
                                    {{ $document['remarks'] ?? '' }}
                                    @break
                                @case('validation_notes')
                                    {{ implode(' | ', $document['validationNotes'] ?? []) }}
                                    @break
                                @default
                                    {{ '' }}
                            @endswitch
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}">No documents matched the current filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
