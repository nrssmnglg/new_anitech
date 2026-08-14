<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Route;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'status' => $user->status,
                    'jobTitle' => $user->officeProfile?->job_title,
                ] : null,
            ],
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'generatedCredentials' => fn () => $request->session()->get('generatedCredentials'),
            ],
            'adminShell' => fn (): ?array => $this->adminShell($request, $user),
        ];
    }

    private function adminShell(Request $request, mixed $user): ?array
    {
        if (! $user || ! in_array($user->role, [User::ROLE_ADMIN, User::ROLE_STAFF], true)) {
            return null;
        }

        $isAdmin = $user->role === User::ROLE_ADMIN;

        return [
            'currentRouteName' => $request->route()?->getName(),
            'searchPlaceholder' => 'Search farmers, records, or files...',
            'homeUrl' => route('admin.home'),
            'logoutUrl' => route('logout'),
            'brand' => [
                'name' => 'AniTech',
                'subtitle' => 'Agriculture System',
                'logoUrl' => asset('figures/anitech-mark-official.svg'),
            ],
            'topbar' => [
                'roleLabel' => $isAdmin ? 'Admin Officer' : 'Staff Officer',
                'regionLabel' => $user->officeProfile?->job_title ?: 'Region IV-A',
                'searchUrl' => route('admin.search.index'),
                'notificationsUrl' => Route::has('admin.notifications.index') ? route('admin.notifications.index') : null,
                'notificationsFeedUrl' => Route::has('admin.notifications.feed') ? route('admin.notifications.feed') : null,
                'notificationsReadAllUrl' => Route::has('admin.notifications.read-all') ? route('admin.notifications.read-all') : null,
                'queriesUrl' => route('admin.queries.index'),
            ],
            'navigation' => $isAdmin
                ? array_values(array_filter([
                    [
                        'label' => 'Core Modules',
                        'items' => array_values(array_filter([
                            [
                                'label' => 'Dashboard',
                                'icon' => 'dashboard',
                                'href' => route('admin.reports.index'),
                                'activePatterns' => ['admin.reports.index'],
                            ],
                            [
                                'label' => 'Farmer Management',
                                'icon' => 'farmers',
                                'children' => [
                                    [
                                        'label' => 'Farmer List',
                                        'href' => route('admin.farmers.index'),
                                        'activePatterns' => ['admin.farmers.index', 'admin.farmers.show', 'admin.farmers.edit'],
                                    ],
                                    [
                                        'label' => 'Application Queue',
                                        'href' => route('admin.membership-applications.index'),
                                        'activePatterns' => ['admin.membership-applications.index'],
                                    ],
                                    [
                                        'label' => 'Membership Application',
                                        'href' => route('admin.membership-applications.create'),
                                        'activePatterns' => ['admin.membership-applications.create'],
                                    ],
                                    [
                                        'label' => 'Encode Old Record',
                                        'href' => route('admin.farmers.create'),
                                        'activePatterns' => ['admin.farmers.create'],
                                    ],
                                ],
                            ],
                            [
                                'label' => 'Renewal Processing',
                                'icon' => 'renewals',
                                'children' => [
                                    [
                                        'label' => 'Renewal Queue',
                                        'href' => route('admin.renewals.index'),
                                        'activePatterns' => ['admin.renewals.index', 'admin.renewals.show', 'admin.renewals.create'],
                                    ],
                                    [
                                        'label' => 'Renewal Records',
                                        'href' => route('admin.renewals.index', ['section' => 'records']),
                                        'activePatterns' => ['admin.renewals.index', 'admin.renewals.show', 'admin.renewals.create'],
                                    ],
                                ],
                            ],
                            [
                                'label' => 'Mortuary Monitoring',
                                'icon' => 'mortuary',
                                'children' => [
                                    [
                                        'label' => 'Claim Queue',
                                        'href' => route('admin.mortuary-claims.index'),
                                        'activePatterns' => ['admin.mortuary-claims.index', 'admin.mortuary-claims.show', 'admin.mortuary-claims.create'],
                                    ],
                                    [
                                        'label' => 'Mortuary Records',
                                        'href' => route('admin.mortuary-claims.index', ['section' => 'records']),
                                        'activePatterns' => ['admin.mortuary-claims.index', 'admin.mortuary-claims.show', 'admin.mortuary-claims.create'],
                                    ],
                                ],
                            ],
                            [
                                'label' => 'Location Management',
                                'icon' => 'locations',
                                'children' => [
                                    [
                                        'label' => 'Barangays',
                                        'href' => route('admin.barangays.index'),
                                        'activePatterns' => ['admin.barangays.index', 'admin.barangays.show', 'admin.barangays.edit', 'admin.barangays.create'],
                                    ],
                                    [
                                        'label' => 'Associations',
                                        'href' => route('admin.associations.index'),
                                        'activePatterns' => ['admin.associations.index', 'admin.associations.show', 'admin.associations.edit', 'admin.associations.create'],
                                    ],
                                ],
                            ],
                            [
                                'label' => 'Fee Configuration',
                                'icon' => 'fees',
                                'children' => [
                                    [
                                        'label' => 'Fee Schedules',
                                        'href' => route('admin.fee-schedules.index'),
                                        'activePatterns' => ['admin.fee-schedules.index', 'admin.fee-schedules.edit', 'admin.fee-schedules.create'],
                                    ],
                                ],
                            ],
                            [
                                'label' => 'Document Requirements',
                                'icon' => 'documents',
                                'children' => [
                                    [
                                        'label' => 'Membership Requirements',
                                        'href' => route('admin.document-requirements.index'),
                                        'activePatterns' => ['admin.document-requirements.index'],
                                    ],
                                ],
                            ],
                            [
                                'label' => 'User Management',
                                'icon' => 'users',
                                'children' => [
                                    [
                                        'label' => 'User Accounts',
                                        'href' => route('admin.users.index'),
                                        'activePatterns' => ['admin.users.index', 'admin.users.show', 'admin.users.edit', 'admin.users.create'],
                                    ],
                                ],
                            ],
                            [
                                'label' => 'Audit Trail',
                                'icon' => 'audit',
                                'children' => [
                                    [
                                        'label' => 'Activity Logs',
                                        'href' => route('admin.audit-logs.index'),
                                        'activePatterns' => ['admin.audit-logs.index'],
                                    ],
                                ],
                            ],
                        ])),
                    ],
                    [
                        'label' => 'Communication',
                        'items' => [
                            [
                                'label' => 'Query & Advisory',
                                'icon' => 'communication',
                                'children' => [
                                    [
                                        'label' => 'Farmer Inquiries',
                                        'href' => route('admin.queries.index'),
                                        'activePatterns' => ['admin.queries.index', 'admin.queries.show'],
                                    ],
                                    [
                                        'label' => 'Notification Management',
                                        'href' => route('admin.notifications.index'),
                                        'activePatterns' => ['admin.notifications.index', 'admin.notifications.show'],
                                    ],
                                    [
                                        'label' => 'Advisories',
                                        'href' => route('admin.advisories.index'),
                                        'activePatterns' => ['admin.advisories.index', 'admin.advisories.show', 'admin.advisories.edit', 'admin.advisories.create'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]))
                : [[
                    'label' => 'Staff Navigation',
                    'items' => [
                        [
                            'label' => 'Dashboard',
                            'icon' => 'dashboard',
                            'href' => route('admin.reports.index'),
                            'activePatterns' => ['admin.reports.index'],
                        ],
                        [
                            'label' => 'Farmer List',
                            'icon' => 'farmers',
                            'href' => route('admin.farmers.index'),
                            'activePatterns' => ['admin.farmers.index', 'admin.farmers.show'],
                        ],
                        [
                            'label' => 'Application Queue',
                            'icon' => 'documents',
                            'href' => route('admin.membership-applications.index'),
                            'activePatterns' => ['admin.membership-applications.index', 'admin.membership-applications.show', 'admin.membership-applications.create'],
                        ],
                        [
                            'label' => 'Renewals',
                            'icon' => 'renewals',
                            'href' => route('admin.renewals.index'),
                            'activePatterns' => ['admin.renewals.index', 'admin.renewals.show', 'admin.renewals.create'],
                        ],
                        [
                            'label' => 'Inquiries',
                            'icon' => 'communication',
                            'href' => route('admin.queries.index'),
                            'activePatterns' => ['admin.queries.index', 'admin.queries.show'],
                        ],
                        [
                            'label' => 'Notifications',
                            'icon' => 'communication',
                            'href' => route('admin.notifications.index'),
                            'activePatterns' => ['admin.notifications.index', 'admin.notifications.show'],
                        ],
                        [
                            'label' => 'Advisories',
                            'icon' => 'communication',
                            'href' => route('admin.advisories.index'),
                            'activePatterns' => ['admin.advisories.index', 'admin.advisories.show', 'admin.advisories.edit', 'admin.advisories.create'],
                        ],
                        [
                            'label' => 'My Tasks',
                            'icon' => 'audit',
                            'href' => route('admin.tasks.index'),
                            'activePatterns' => ['admin.tasks.index'],
                        ],
                    ],
                ]],
        ];
    }
}
