@extends('layouts.app')

@section('title', 'Notifications')
@section('header_actions')
    <div data-admin-mark-all {{ $summary['unread'] > 0 ? '' : 'hidden' }}>
        <form method="POST" action="{{ route('admin.notifications.read-all') }}">
            @csrf
            <button type="submit" class="ghost-btn">Mark All As Read</button>
        </form>
    </div>
@endsection

@section('content')
    <section class="panel" style="margin-bottom: 24px;">
        <div class="panel-header panel-header--tight" style="margin-bottom: 18px;">
            <div>
                <h2 style="margin: 0;">Notification Management Center</h2>
                <p style="margin: 6px 0 0; color: #64748b;">Monitor notification history, delivery status, failures, scheduled reminders, and resend actions.</p>
            </div>
        </div>

        <div style="display: grid; gap: 16px; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
            <article class="panel" style="padding: 18px; margin: 0;">
                <div style="font-size: 12px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #64748b;">Total Dispatches</div>
                <div style="margin-top: 8px; font-size: 34px; font-weight: 900; color: #0f172a;">{{ $managementSummary['totalDispatches'] }}</div>
            </article>
            <article class="panel" style="padding: 18px; margin: 0;">
                <div style="font-size: 12px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #64748b;">Queued</div>
                <div style="margin-top: 8px; font-size: 34px; font-weight: 900; color: #b56a00;">{{ $managementSummary['queuedDispatches'] }}</div>
            </article>
            <article class="panel" style="padding: 18px; margin: 0;">
                <div style="font-size: 12px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #64748b;">Delivered Recipients</div>
                <div style="margin-top: 8px; font-size: 34px; font-weight: 900; color: #166534;">{{ $managementSummary['deliveredRecipients'] }}</div>
            </article>
            <article class="panel" style="padding: 18px; margin: 0;">
                <div style="font-size: 12px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #64748b;">Failed Recipients</div>
                <div style="margin-top: 8px; font-size: 34px; font-weight: 900; color: #be123c;">{{ $managementSummary['failedRecipients'] }}</div>
            </article>
            <article class="panel" style="padding: 18px; margin: 0;">
                <div style="font-size: 12px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #64748b;">Scheduled Reminders</div>
                <div style="margin-top: 8px; font-size: 34px; font-weight: 900; color: #1d4ed8;">{{ $managementSummary['scheduledReminders'] }}</div>
            </article>
        </div>
    </section>

    <section class="panel" data-admin-notification-panel data-admin-notification-live-list="{{ $notifications->currentPage() === 1 ? 'true' : 'false' }}">
        <div class="panel-header panel-header--tight notification-panel__header" style="margin-bottom: 18px;">
            <div>
                <h2 style="margin: 0;">Admin Notification Feed</h2>
            </div>
            <form method="GET" action="{{ route('admin.notifications.index') }}" class="notification-filter-form" data-admin-notification-filter-form>
                <label>
                    <span>Module</span>
                    <select name="module">
                        @foreach ($moduleOptions as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['module'] ?? 'all') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>Status</span>
                    <select name="state">
                        @foreach ($stateOptions as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['state'] ?? 'all') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>Delivery</span>
                    <select name="delivery">
                        <option value="all" @selected(($filters['delivery'] ?? 'all') === 'all')>All Delivery States</option>
                        <option value="queued" @selected(($filters['delivery'] ?? 'all') === 'queued')>Queued</option>
                        <option value="delivered" @selected(($filters['delivery'] ?? 'all') === 'delivered')>Delivered</option>
                        <option value="failed" @selected(($filters['delivery'] ?? 'all') === 'failed')>Failed</option>
                        <option value="read" @selected(($filters['delivery'] ?? 'all') === 'read')>Read</option>
                    </select>
                </label>
                <div class="notification-filter-form__actions">
                    <button type="submit" class="btn-top">Apply Filters</button>
                    <a href="{{ route('admin.notifications.index') }}" class="ghost-btn">Reset</a>
                </div>
            </form>
        </div>

        <table class="modern-table--green">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Subject</th>
                    <th>Message</th>
                    <th>Received</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody data-admin-notification-list>
                @include('admin.notifications._rows', ['notifications' => $notifications])
            </tbody>
        </table>
    </section>

    <div style="margin-top: 24px;">
        {{ $notifications->links('pagination::tailwind') }}
    </div>

    <section class="panel" style="margin-top: 28px;">
        <div class="panel-header panel-header--tight" style="margin-bottom: 18px;">
            <div>
                <h2 style="margin: 0;">Notification History</h2>
                <p style="margin: 6px 0 0; color: #64748b;">Full dispatch-level history with recipient delivery counts and resend controls.</p>
            </div>
        </div>

        <table class="modern-table--green">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Channel</th>
                    <th>Recipients</th>
                    <th>Delivery</th>
                    <th>Queued</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($dispatches as $dispatch)
                    <tr>
                        <td>
                            <div style="font-weight: 700; color: #0f172a;">{{ $dispatch->type_label }}</div>
                            <div class="notification-row__badges">
                                <span class="badge badge-neutral">{{ $dispatch->module_label }}</span>
                            </div>
                            <div style="margin-top: 6px; color: #64748b;">{{ $dispatch->subject }}</div>
                        </td>
                        <td>{{ strtoupper((string) $dispatch->channel) }}</td>
                        <td>
                            <div>Total: {{ (int) $dispatch->recipient_count }}</div>
                            <div style="margin-top: 6px; color: #166534;">Delivered: {{ (int) $dispatch->delivered_count }}</div>
                            <div style="color: #be123c;">Failed: {{ (int) $dispatch->failed_count }}</div>
                            <div style="color: #475569;">Read: {{ (int) $dispatch->read_count }}</div>
                        </td>
                        <td>
                            <span class="badge {{ $dispatch->delivery_label === 'Failed' ? 'badge-rejected' : ($dispatch->delivery_label === 'Delivered' ? 'badge-approved' : 'badge-pending') }}">
                                {{ $dispatch->delivery_label }}
                            </span>
                        </td>
                        <td>{{ optional(\Carbon\Carbon::parse($dispatch->queued_at ?: $dispatch->created_at))->format('M d, Y h:i A') }}</td>
                        <td>
                            <div class="table-actions" style="justify-content: flex-end;">
                                <a href="{{ $dispatch->show_url }}" class="ghost-btn" style="padding: 8px 12px;">View Details</a>
                                @if ($dispatch->target_url)
                                    <a href="{{ $dispatch->target_url }}" class="notification-action-btn notification-action-btn--primary" aria-label="Open source record" title="Open source record">
                                        <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                            <path d="M7.5 12.5L12.5 7.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M8.75 7.5H12.5V11.25" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M6.25 5.625H5.625A1.875 1.875 0 0 0 3.75 7.5v6.875A1.875 1.875 0 0 0 5.625 16.25H12.5a1.875 1.875 0 0 0 1.875-1.875v-.625" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </a>
                                @endif
                                <form method="POST" action="{{ $dispatch->resend_url }}">
                                    @csrf
                                    <button type="submit" class="notification-action-btn notification-action-btn--ghost" aria-label="Resend notification" title="Resend notification">
                                        <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                            <path d="M16.25 10A6.25 6.25 0 1 1 14.4 5.56" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M16.25 3.75V7.5H12.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: #64748b; padding: 28px;">No notification dispatches found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <div style="margin-top: 24px;">
        {{ $dispatches->links('pagination::tailwind') }}
    </div>

    <section style="display: grid; gap: 24px; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); margin-top: 28px;">
        <article class="panel">
            <div class="panel-header panel-header--tight" style="margin-bottom: 18px;">
                <div>
                    <h2 style="margin: 0;">Failed Notifications</h2>
                    <p style="margin: 6px 0 0; color: #64748b;">Recipients with delivery failures and recorded failure reasons.</p>
                </div>
            </div>

            <div style="display: grid; gap: 12px;">
                @forelse ($failedRecipients as $failure)
                    <article style="border: 1px solid #fecdd3; background: #fff1f2; border-radius: 16px; padding: 16px;">
                        <div style="font-weight: 800; color: #881337;">{{ $failure->type_label }}</div>
                        <div style="margin-top: 6px; color: #475569;">{{ $failure->subject }}</div>
                        <div style="margin-top: 8px; font-size: 14px; color: #334155;">Recipient: {{ $failure->recipient_address ?: 'No address' }}</div>
                        <div style="margin-top: 8px; font-size: 14px; color: #9f1239;">{{ $failure->failure_reason ?: 'No failure reason recorded.' }}</div>
                        <div style="margin-top: 8px; font-size: 13px; color: #64748b;">{{ optional(\Carbon\Carbon::parse($failure->failed_at))->format('M d, Y h:i A') }}</div>
                    </article>
                @empty
                    <p style="color: #64748b; margin: 0;">No failed notifications found.</p>
                @endforelse
            </div>
        </article>

        <article class="panel">
            <div class="panel-header panel-header--tight" style="margin-bottom: 18px;">
                <div>
                    <h2 style="margin: 0;">Scheduled Reminders</h2>
                    <p style="margin: 6px 0 0; color: #64748b;">Queued reminder notifications waiting to be sent or processed.</p>
                </div>
            </div>

            <div style="display: grid; gap: 12px;">
                @forelse ($scheduledReminders as $reminder)
                    <article style="border: 1px solid #bfdbfe; background: #eff6ff; border-radius: 16px; padding: 16px;">
                        <div style="display: flex; align-items: start; justify-content: space-between; gap: 12px;">
                            <div>
                                <div style="font-weight: 800; color: #1d4ed8;">{{ $reminder->type_label }}</div>
                                <div style="margin-top: 6px; color: #475569;">{{ $reminder->subject }}</div>
                            </div>
                            <form method="POST" action="{{ route('admin.notifications.resend', $reminder->id) }}">
                                @csrf
                                <button type="submit" class="ghost-btn">Resend</button>
                            </form>
                        </div>
                        <div style="margin-top: 8px; font-size: 14px; color: #334155;">Recipients: {{ (int) $reminder->recipient_count }}</div>
                        <div style="margin-top: 8px; font-size: 13px; color: #64748b;">Queued at {{ optional(\Carbon\Carbon::parse($reminder->queued_at ?: $reminder->created_at))->format('M d, Y h:i A') }}</div>
                    </article>
                @empty
                    <p style="color: #64748b; margin: 0;">No scheduled reminders found.</p>
                @endforelse
            </div>
        </article>
    </section>
@endsection
