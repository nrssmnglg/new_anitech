@forelse ($notifications as $notification)
    @php($recipientKey = app(\App\Services\Routing\PublicRouteKeyService::class)->encode($notification->recipient_id))
    <tr>
        <td>
            <div>{{ $notification->type_label }}</div>
            <div class="notification-row__badges">
                <span class="badge badge-neutral">{{ $notification->module_label }}</span>
                @if ($notification->source_label)
                    <span class="badge badge-neutral">{{ $notification->source_label }}</span>
                @endif
            </div>
        </td>
        <td style="font-weight: 700; color: #0f172a;">{{ $notification->subject }}</td>
        <td style="max-width: 420px; color: #475569;">{{ $notification->message }}</td>
        <td>{{ \Carbon\Carbon::parse($notification->created_at)->format('M d, Y h:i A') }}</td>
        <td>
            <span class="badge {{ $notification->read_at ? 'badge-neutral' : 'badge-pending' }}">
                {{ $notification->read_at ? 'Read' : 'Unread' }}
            </span>
        </td>
        <td>
            <div class="table-actions">
                @if ($notification->target_url)
                    <form method="POST" action="{{ route('admin.notifications.open', $recipientKey) }}">
                        @csrf
                        <button type="submit" class="notification-action-btn notification-action-btn--primary" aria-label="Open record" title="Open record">
                            <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                <path d="M7.5 12.5L12.5 7.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M8.75 7.5H12.5V11.25" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M6.25 5.625H5.625A1.875 1.875 0 0 0 3.75 7.5v6.875A1.875 1.875 0 0 0 5.625 16.25H12.5a1.875 1.875 0 0 0 1.875-1.875v-.625" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                    </form>
                @endif

                @unless ($notification->read_at)
                    <form method="POST" action="{{ route('admin.notifications.read', $recipientKey) }}">
                        @csrf
                        <button type="submit" class="notification-action-btn notification-action-btn--ghost" aria-label="Mark as read" title="Mark as read">
                            <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                                <path d="M5 10.5L8.125 13.625L15 6.75" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                    </form>
                @endunless
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="6" style="text-align: center; color: #64748b; padding: 28px;">No notifications found for this account.</td>
    </tr>
@endforelse
