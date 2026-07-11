@php
    $user = auth()->user();
    $userRole = $user?->role ?? 'admin';
    $isAdmin = $userRole === \App\Models\User::ROLE_ADMIN;
    $userName = $user?->name ?? 'System Administrator';
    $initial = strtoupper(substr($userName, 0, 1));

    $farmerManagementOpen = request()->routeIs('admin.farmers.*') || request()->routeIs('admin.membership-applications.*');
    $renewalOpen = request()->routeIs('admin.renewals.*');
    $mortuaryOpen = request()->routeIs('admin.mortuary-claims.*');
    $locationOpen = $isAdmin && (request()->routeIs('admin.barangays.*') || request()->routeIs('admin.associations.*'));
    $feeConfigOpen = $isAdmin && request()->routeIs('admin.fee-schedules.*');
    $documentRequirementOpen = $isAdmin && request()->routeIs('admin.document-requirements.*');
    $userManagementOpen = $isAdmin && request()->routeIs('admin.users.*');
    $auditTrailOpen = $isAdmin && request()->routeIs('admin.audit-logs.*');
    $communicationsOpen = request()->routeIs('admin.queries.*') || request()->routeIs('admin.advisories.*');
@endphp

<div class="sidebar-overlay" data-admin-sidebar-close hidden></div>

<aside class="sidebar" id="admin-sidebar" aria-label="Admin navigation">
    <div class="sidebar-main">
        <div class="sidebar-header">
            <div class="sidebar-header__row">
                <a href="{{ route('admin.home') }}" class="brand-link">
                    <div class="brand">
                        <img src="{{ asset('figures/anitech-mark-official.svg') }}" alt="AniTech" class="brand-logo">
                        <span>AniTech</span>
                    </div>
                    <div class="brand-sub">Agriculture System</div>
                </a>
                <button type="button" class="sidebar-close-btn" data-admin-sidebar-close aria-label="Close navigation menu">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="m6 6 12 12"/>
                        <path d="m18 6-12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <div class="nav-menu">
            <div class="nav-group-title">Core Modules</div>

            <div id="farmerModule" class="menu-item-container {{ $farmerManagementOpen ? 'open' : '' }}">
                <button type="button" class="nav-item {{ $farmerManagementOpen ? 'active' : '' }}" data-menu-target="farmerModule">
                    <span class="nav-copy">
                        <span class="nav-icon">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="14" rx="2"/><path d="M7 8h4"/><path d="M7 12h10"/></svg>
                        </span>
                        <span>Farmer Management</span>
                    </span>
                    <span class="nav-arrow">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                    </span>
                </button>
                <div class="submenu">
                    <a href="{{ route('admin.farmers.index') }}" class="{{ request()->routeIs('admin.farmers.index') || request()->routeIs('admin.farmers.show') || request()->routeIs('admin.farmers.edit') ? 'active-sub' : '' }}">Farmer List</a>
                    <a href="{{ route('admin.membership-applications.index') }}" class="{{ request()->routeIs('admin.membership-applications.index') ? 'active-sub' : '' }}">Application Queue</a>
                    <a href="{{ route('admin.membership-applications.create') }}" class="{{ request()->routeIs('admin.membership-applications.create') ? 'active-sub' : '' }}">Membership Application</a>
                </div>
            </div>

            <div id="renewalModule" class="menu-item-container {{ $renewalOpen ? 'open' : '' }}">
                <button type="button" class="nav-item {{ $renewalOpen ? 'active' : '' }}" data-menu-target="renewalModule">
                    <span class="nav-copy">
                        <span class="nav-icon">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-2.64-6.36"/><path d="M21 3v6h-6"/></svg>
                        </span>
                        <span>Renewal Processing</span>
                    </span>
                    <span class="nav-arrow">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                    </span>
                </button>
                <div class="submenu">
                    <a href="{{ route('admin.renewals.index', ['section' => 'records']) }}" class="{{ request()->routeIs('admin.renewals.index') && request('section') === 'records' ? 'active-sub' : '' }}">Renewal Records</a>
                </div>
            </div>

            <div id="mortuaryModule" class="menu-item-container {{ $mortuaryOpen ? 'open' : '' }}">
                <button type="button" class="nav-item {{ $mortuaryOpen ? 'active' : '' }}" data-menu-target="mortuaryModule">
                    <span class="nav-copy">
                        <span class="nav-icon">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-4.35-7-10a4 4 0 0 1 7-2.65A4 4 0 0 1 19 11c0 5.65-7 10-7 10Z"/><path d="M9 12h6"/><path d="M12 9v6"/></svg>
                        </span>
                        <span>Mortuary Monitoring</span>
                    </span>
                    <span class="nav-arrow">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                    </span>
                </button>
                <div class="submenu">
                    <a href="{{ route('admin.mortuary-claims.index') }}" class="{{ (request()->routeIs('admin.mortuary-claims.index') && request('section') !== 'records') || request()->routeIs('admin.mortuary-claims.show') || request()->routeIs('admin.mortuary-claims.create') ? 'active-sub' : '' }}">Claim Queue</a>
                    <a href="{{ route('admin.mortuary-claims.index', ['section' => 'records']) }}#mortuary-records" class="{{ request()->routeIs('admin.mortuary-claims.index') && request('section') === 'records' ? 'active-sub' : '' }}">Mortuary Records</a>
                </div>
            </div>

            @if ($isAdmin)
                <div id="locationModule" class="menu-item-container {{ $locationOpen ? 'open' : '' }}">
                    <button type="button" class="nav-item {{ $locationOpen ? 'active' : '' }}" data-menu-target="locationModule">
                        <span class="nav-copy">
                            <span class="nav-icon">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-6-4.35-6-10a6 6 0 1 1 12 0c0 5.65-6 10-6 10Z"/><circle cx="12" cy="11" r="2"/></svg>
                            </span>
                            <span>Location Management</span>
                        </span>
                        <span class="nav-arrow">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                        </span>
                    </button>
                    <div class="submenu">
                        <a href="{{ route('admin.barangays.index') }}" class="{{ request()->routeIs('admin.barangays.*') ? 'active-sub' : '' }}">Barangays</a>
                        <a href="{{ route('admin.associations.index') }}" class="{{ request()->routeIs('admin.associations.*') ? 'active-sub' : '' }}">Associations</a>
                    </div>
                </div>

                <div id="feeModule" class="menu-item-container {{ $feeConfigOpen ? 'open' : '' }}">
                    <button type="button" class="nav-item {{ $feeConfigOpen ? 'active' : '' }}" data-menu-target="feeModule">
                        <span class="nav-copy">
                            <span class="nav-icon">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                            </span>
                            <span>Fee Configuration</span>
                        </span>
                        <span class="nav-arrow">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                        </span>
                    </button>
                    <div class="submenu">
                        <a href="{{ route('admin.fee-schedules.index') }}" class="{{ request()->routeIs('admin.fee-schedules.*') ? 'active-sub' : '' }}">Fee Schedules</a>
                    </div>
                </div>

                <div id="documentRequirementModule" class="menu-item-container {{ $documentRequirementOpen ? 'open' : '' }}">
                    <button type="button" class="nav-item {{ $documentRequirementOpen ? 'active' : '' }}" data-menu-target="documentRequirementModule">
                        <span class="nav-copy">
                            <span class="nav-icon">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 3h6"/><path d="M10 8h4"/><path d="M5 5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5Z"/><path d="M9 13h6"/><path d="M9 17h6"/></svg>
                            </span>
                            <span>Document Requirements</span>
                        </span>
                        <span class="nav-arrow">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                        </span>
                    </button>
                    <div class="submenu">
                        <a href="{{ route('admin.document-requirements.index') }}" class="{{ request()->routeIs('admin.document-requirements.*') ? 'active-sub' : '' }}">Membership Requirements</a>
                    </div>
                </div>

                <div id="userModule" class="menu-item-container {{ $userManagementOpen ? 'open' : '' }}">
                    <button type="button" class="nav-item {{ $userManagementOpen ? 'active' : '' }}" data-menu-target="userModule">
                        <span class="nav-copy">
                            <span class="nav-icon">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            </span>
                            <span>User Management</span>
                        </span>
                        <span class="nav-arrow">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                        </span>
                    </button>
                    <div class="submenu">
                        <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active-sub' : '' }}">User Accounts</a>
                    </div>
                </div>

                <div id="auditModule" class="menu-item-container {{ $auditTrailOpen ? 'open' : '' }}">
                    <button type="button" class="nav-item {{ $auditTrailOpen ? 'active' : '' }}" data-menu-target="auditModule">
                        <span class="nav-copy">
                            <span class="nav-icon">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l7 4v5c0 5-3.5 8.5-7 9-3.5-.5-7-4-7-9V7l7-4Z"/><path d="M9 12l2 2 4-4"/></svg>
                            </span>
                            <span>Audit Trail</span>
                        </span>
                        <span class="nav-arrow">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                        </span>
                    </button>
                    <div class="submenu">
                        <a href="{{ route('admin.audit-logs.index') }}" class="{{ request()->routeIs('admin.audit-logs.*') ? 'active-sub' : '' }}">Activity Logs</a>
                    </div>
                </div>
            @endif

            <div class="nav-group-title" style="margin-top: 14px;">Communication</div>

            <div id="communicationsModule" class="menu-item-container {{ $communicationsOpen ? 'open' : '' }}">
                <button type="button" class="nav-item {{ $communicationsOpen ? 'active' : '' }}" data-menu-target="communicationsModule">
                    <span class="nav-copy">
                        <span class="nav-icon">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2Z"/><path d="M8 10h8"/><path d="M8 14h5"/></svg>
                        </span>
                        <span>Query &amp; Advisory</span>
                    </span>
                    <span class="nav-arrow">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                    </span>
                </button>
                <div class="submenu">
                    <a href="{{ route('admin.queries.index') }}" class="{{ request()->routeIs('admin.queries.*') ? 'active-sub' : '' }}">Farmer Inquiries</a>
                    <a href="{{ route('admin.advisories.index') }}" class="{{ request()->routeIs('admin.advisories.*') ? 'active-sub' : '' }}">Advisories</a>
                </div>
            </div>
        </div>
    </div>

    <div class="sidebar-footer">
        <div class="user-card">
            <div class="user-main">
                <div class="avatar">{{ $initial }}</div>
                <div>
                    <div style="font-weight: 700;">{{ $userName }}</div>
                    <div style="color: var(--text-muted); font-size: 0.85rem;">{{ $userRole }}</div>
                </div>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}" onsubmit="return confirm('Are you sure you want to log out?');">
            @csrf
            <button type="submit" class="logout-link">Log Out</button>
        </form>
    </div>
</aside>

