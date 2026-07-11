@extends('layouts.app')

@section('title', 'Notification Detail')
@section('subtitle', 'Dispatch-level recipient history, delivery tracking, and resend controls.')
@section('header_actions')
    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
        <a href="{{ route('admin.notifications.index') }}" class="ghost-btn">Back to Notifications</a>
        <form method="POST" action="{{ $dispatch->resend_url }}">
            @csrf
            <button type="submit" class="btn-top">Resend Notification</button>
        </form>
    </div>
@endsection

@section('content')
    <section class="panel" style="margin-bottom: 24px;">
        <div class="panel-header panel-header--tight" style="margin-bottom: 18px;">
            <div>
                <h2 style="margin: 0;">{{ $dispatch->type_label }}</h2>
                <p style="margin: 6px 0 0; color: #64748b;">{{ $dispatch->subject }}</p>
            </div>
            <span class="badge {{ $dispatch->delivery_label === 'Failed' ? 'badge-rejected' : ($dispatch->delivery_label === 'Delivered' ? 'badge-approved' : 'badge-pending') }}">
                {{ $dispatch->delivery_label }}
            </span>
        </div>

        <div style="display: grid; gap: 16px; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
            <article class="panel" style="padding: 18px; margin: 0;">
                <div style="font-size: 12px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #64748b;">Module</div>
                <div style="margin-top: 8px; font-size: 20px; font-weight: 800; color: #0f172a;">{{ $dispatch->module_label }}</div>
            </article>
            <article class="panel" style="padding: 18px; margin: 0;">
                <div style="font-size: 12px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #64748b;">Channel</div>
                <div style="margin-top: 8px; font-size: 20px; font-weight: 800; color: #0f172a;">{{ strtoupper((string) $dispatch->channel) }}</div>
            </article>
            <article class="panel" style="padding: 18px; margin: 0;">
                <div style="font-size: 12px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #64748b;">Recipients</div>
                <div style="margin-top: 8px; font-size: 20px; font-weight: 800; color: #0f172a;">{{ (int) $dispatch->recipient_count }}</div>
            </article>
            <article class="panel" style="padding: 18px; margin: 0;">
                <div style="font-size: 12px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #64748b;">Delivered</div>
                <div style="margin-top: 8px; font-size: 20px; font-weight: 800; color: #166534;">{{ (int) $dispatch->delivered_count }}</div>
            </article>
            <article class="panel" style="padding: 18px; margin: 0;">
                <div style="font-size: 12px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #64748b;">Failed</div>
                <div style="margin-top: 8px; font-size: 20px; font-weight: 800; color: #be123c;">{{ (int) $dispatch->failed_count }}</div>
            </article>
            <article class="panel" style="padding: 18px; margin: 0;">
                <div style="font-size: 12px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #64748b;">Read</div>
                <div style="margin-top: 8px; font-size: 20px; font-weight: 800; color: #1d4ed8;">{{ (int) $dispatch->read_count }}</div>
            </article>
        </div>

        @if ($dispatch->message)
            <div class="panel" style="padding: 18px; margin-top: 18px;">
                <div style="font-size: 12px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #64748b;">Message</div>
                <p style="margin: 10px 0 0; color: #334155;">{{ $dispatch->message }}</p>
            </div>
        @endif
    </section>

    <section class="panel">
        <div class="panel-header panel-header--tight" style="margin-bottom: 18px;">
            <div>
                <h2 style="margin: 0;">Recipients</h2>
                <p style="margin: 6px 0 0; color: #64748b;">Per-recipient status, delivery timestamps, read state, and failure reasons.</p>
            </div>
        </div>

        <table class="modern-table--green">
            <thead>
                <tr>
                    <th>Recipient</th>
                    <th>Address</th>
                    <th>Status</th>
                    <th>Delivered</th>
                    <th>Read</th>
                    <th>Failed</th>
                    <th>Failure Reason</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recipients as $recipient)
                    <tr>
                        <td>
                            <div style="font-weight: 700; color: #0f172a;">{{ $recipient->user_name ?: ($recipient->farmer_code ?: 'Recipient #' . $recipient->id) }}</div>
                            @if ($recipient->farmer_code)
                                <div style="color: #64748b;">{{ $recipient->farmer_code }}</div>
                            @endif
                        </td>
                        <td>{{ $recipient->recipient_address ?: 'No address' }}</td>
                        <td>
                            <span class="badge {{ $recipient->status === 'failed' ? 'badge-rejected' : ($recipient->status === 'delivered' ? 'badge-approved' : 'badge-pending') }}">
                                {{ $recipient->status_label }}
                            </span>
                        </td>
                        <td>{{ $recipient->delivered_at ? \Carbon\Carbon::parse($recipient->delivered_at)->format('M d, Y h:i A') : '-' }}</td>
                        <td>{{ $recipient->read_at ? \Carbon\Carbon::parse($recipient->read_at)->format('M d, Y h:i A') : '-' }}</td>
                        <td>{{ $recipient->failed_at ? \Carbon\Carbon::parse($recipient->failed_at)->format('M d, Y h:i A') : '-' }}</td>
                        <td style="max-width: 320px; color: #475569;">{{ $recipient->failure_reason ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; color: #64748b; padding: 28px;">No recipients found for this notification.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
@endsection
