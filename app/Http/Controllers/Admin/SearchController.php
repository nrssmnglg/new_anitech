<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Advisory;
use App\Models\Association;
use App\Models\Barangay;
use App\Models\Farmer;
use App\Models\MembershipApplication;
use App\Models\Query;
use App\Models\RenewalRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function index(Request $request): Response
    {
        $term = trim((string) $request->string('search'));
        $isAdmin = $request->user()?->hasRole(User::ROLE_ADMIN) ?? false;

        $results = [
            $this->farmers($term),
            $this->membershipApplications($term),
            $this->renewals($term),
            $this->queries($term),
        ];

        if ($isAdmin) {
            $results[] = $this->advisories($term);
            $results[] = $this->users($term);
            $results[] = $this->barangays($term);
            $results[] = $this->associations($term);
        }

        return Inertia::render('Admin/Search/Index', [
            'search' => [
                'term' => $term,
                'hasQuery' => $term !== '',
                'totalResults' => collect($results)->sum(fn (array $group): int => count($group['items'])),
                'groups' => collect($results)
                    ->filter(fn (array $group): bool => $term === '' || count($group['items']) > 0)
                    ->values()
                    ->all(),
            ],
        ]);
    }

    private function farmers(string $term): array
    {
        $items = $term === ''
            ? []
            : Farmer::query()
                ->with(['profile:id,farmer_id,first_name,middle_name,last_name,suffix', 'barangay:id,name'])
                ->where(function (Builder $query) use ($term): void {
                    $query->where('farmer_code', 'like', "%{$term}%")
                        ->orWhereHas('profile', function (Builder $profileQuery) use ($term): void {
                            $profileQuery
                                ->where('first_name', 'like', "%{$term}%")
                                ->orWhere('middle_name', 'like', "%{$term}%")
                                ->orWhere('last_name', 'like', "%{$term}%")
                                ->orWhere('mobile_number', 'like', "%{$term}%")
                                ->orWhere('address', 'like', "%{$term}%");
                        });
                })
                ->latest('id')
                ->limit(8)
                ->get()
                ->map(fn (Farmer $farmer): array => [
                    'id' => $farmer->id,
                    'title' => $farmer->full_name,
                    'subtitle' => trim(collect([
                        $farmer->farmer_code,
                        $farmer->barangay?->name,
                        $farmer->status->label(),
                    ])->filter()->implode(' | ')),
                    'url' => route('admin.farmers.show', $farmer),
                ])
                ->all();

        return $this->group('Farmers', 'Farmer registry matches', $items, route('admin.farmers.index', ['search' => $term]));
    }

    private function membershipApplications(string $term): array
    {
        $items = $term === ''
            ? []
            : MembershipApplication::query()
                ->with(['farmer.profile:id,farmer_id,first_name,middle_name,last_name,suffix', 'farmer:id,farmer_code'])
                ->where(function (Builder $query) use ($term): void {
                    $query->where('application_no', 'like', "%{$term}%")
                        ->orWhereHas('farmer', function (Builder $farmerQuery) use ($term): void {
                            $farmerQuery->where('farmer_code', 'like', "%{$term}%")
                                ->orWhereHas('profile', function (Builder $profileQuery) use ($term): void {
                                    $profileQuery
                                        ->where('first_name', 'like', "%{$term}%")
                                        ->orWhere('middle_name', 'like', "%{$term}%")
                                        ->orWhere('last_name', 'like', "%{$term}%");
                                });
                        });
                })
                ->latest('id')
                ->limit(8)
                ->get()
                ->map(fn (MembershipApplication $application): array => [
                    'id' => $application->id,
                    'title' => $application->application_no ?: 'Application #' . $application->id,
                    'subtitle' => trim(collect([
                        $application->farmer?->full_name,
                        $application->farmer?->farmer_code,
                        $application->status?->label() ?? 'Pending',
                    ])->filter()->implode(' | ')),
                    'url' => route('admin.membership-applications.show', $application),
                ])
                ->all();

        return $this->group('Applications', 'Membership application queue', $items, route('admin.membership-applications.index', ['search' => $term]));
    }

    private function renewals(string $term): array
    {
        $items = $term === ''
            ? []
            : RenewalRequest::query()
                ->with(['farmer.profile:id,farmer_id,first_name,middle_name,last_name,suffix', 'farmer:id,farmer_code'])
                ->where(function (Builder $query) use ($term): void {
                    $query->where('application_no', 'like', "%{$term}%")
                        ->orWhereHas('farmer', function (Builder $farmerQuery) use ($term): void {
                            $farmerQuery->where('farmer_code', 'like', "%{$term}%")
                                ->orWhereHas('profile', function (Builder $profileQuery) use ($term): void {
                                    $profileQuery
                                        ->where('first_name', 'like', "%{$term}%")
                                        ->orWhere('middle_name', 'like', "%{$term}%")
                                        ->orWhere('last_name', 'like', "%{$term}%");
                                });
                        });
                })
                ->latest('id')
                ->limit(8)
                ->get()
                ->map(fn (RenewalRequest $renewal): array => [
                    'id' => $renewal->id,
                    'title' => $renewal->application_no ?: 'Renewal #' . $renewal->id,
                    'subtitle' => trim(collect([
                        $renewal->farmer?->full_name,
                        $renewal->farmer?->farmer_code,
                        $renewal->status?->label() ?? 'Pending',
                    ])->filter()->implode(' | ')),
                    'url' => route('admin.renewals.show', $renewal),
                ])
                ->all();

        return $this->group('Renewals', 'Renewal processing records', $items, route('admin.renewals.index', ['section' => 'records', 'record_search' => $term]));
    }

    private function queries(string $term): array
    {
        $items = $term === ''
            ? []
            : Query::query()
                ->with(['farmer.profile:id,farmer_id,first_name,middle_name,last_name,suffix', 'farmer:id,farmer_code'])
                ->where(function (Builder $query) use ($term): void {
                    $query->where('subject', 'like', "%{$term}%")
                        ->orWhere('message', 'like', "%{$term}%")
                        ->orWhereHas('farmer', function (Builder $farmerQuery) use ($term): void {
                            $farmerQuery->where('farmer_code', 'like', "%{$term}%")
                                ->orWhereHas('profile', function (Builder $profileQuery) use ($term): void {
                                    $profileQuery
                                        ->where('first_name', 'like', "%{$term}%")
                                        ->orWhere('middle_name', 'like', "%{$term}%")
                                        ->orWhere('last_name', 'like', "%{$term}%");
                                });
                        });
                })
                ->latest('id')
                ->limit(8)
                ->get()
                ->map(fn (Query $query): array => [
                    'id' => $query->id,
                    'title' => $query->subject,
                    'subtitle' => trim(collect([
                        $query->farmer?->full_name,
                        $query->farmer?->farmer_code,
                        $query->status,
                    ])->filter()->implode(' | ')),
                    'url' => route('admin.queries.show', $query),
                ])
                ->all();

        return $this->group('Inquiries', 'Farmer queries and responses', $items, route('admin.queries.index'));
    }

    private function advisories(string $term): array
    {
        $items = $term === ''
            ? []
            : Advisory::query()
                ->where('title', 'like', "%{$term}%")
                ->orWhere('content', 'like', "%{$term}%")
                ->latest('id')
                ->limit(8)
                ->get()
                ->map(fn (Advisory $advisory): array => [
                    'id' => $advisory->id,
                    'title' => $advisory->title,
                    'subtitle' => trim(collect([
                        $advisory->status,
                        $advisory->audience_type === 'all' ? 'All farmers' : ucfirst((string) $advisory->audience_type),
                    ])->filter()->implode(' | ')),
                    'url' => route('admin.advisories.show', $advisory),
                ])
                ->all();

        return $this->group('Advisories', 'Published and draft advisories', $items, route('admin.advisories.index'));
    }

    private function users(string $term): array
    {
        $items = $term === ''
            ? []
            : User::query()
                ->with('officeProfile:id,user_id,employee_id,job_title')
                ->where(function (Builder $query) use ($term): void {
                    $query->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhereHas('officeProfile', function (Builder $profileQuery) use ($term): void {
                            $profileQuery
                                ->where('employee_id', 'like', "%{$term}%")
                                ->orWhere('job_title', 'like', "%{$term}%");
                        });
                })
                ->latest('id')
                ->limit(8)
                ->get()
                ->map(fn (User $user): array => [
                    'id' => $user->id,
                    'title' => $user->name,
                    'subtitle' => trim(collect([
                        $user->email,
                        $user->role,
                        $user->officeProfile?->employee_id,
                    ])->filter()->implode(' | ')),
                    'url' => route('admin.users.show', $user),
                ])
                ->all();

        return $this->group('Users', 'Office and farmer user accounts', $items, route('admin.users.index'));
    }

    private function barangays(string $term): array
    {
        $items = $term === ''
            ? []
            : Barangay::query()
                ->where('name', 'like', "%{$term}%")
                ->orWhere('code', 'like', "%{$term}%")
                ->orderBy('name')
                ->limit(8)
                ->get()
                ->map(fn (Barangay $barangay): array => [
                    'id' => $barangay->id,
                    'title' => $barangay->name,
                    'subtitle' => trim(collect([
                        $barangay->code,
                        $barangay->status,
                    ])->filter()->implode(' | ')),
                    'url' => route('admin.barangays.show', $barangay),
                ])
                ->all();

        return $this->group('Barangays', 'Location records', $items, route('admin.barangays.index', ['search' => $term]));
    }

    private function associations(string $term): array
    {
        $items = $term === ''
            ? []
            : Association::query()
                ->with('barangay:id,name')
                ->where(function (Builder $query) use ($term): void {
                    $query->where('name', 'like', "%{$term}%")
                        ->orWhere('code', 'like', "%{$term}%")
                        ->orWhereHas('barangay', function (Builder $barangayQuery) use ($term): void {
                            $barangayQuery
                                ->where('name', 'like', "%{$term}%")
                                ->orWhere('code', 'like', "%{$term}%");
                        });
                })
                ->orderBy('name')
                ->limit(8)
                ->get()
                ->map(fn (Association $association): array => [
                    'id' => $association->id,
                    'title' => $association->name,
                    'subtitle' => trim(collect([
                        $association->code,
                        $association->barangay?->name,
                        $association->status,
                    ])->filter()->implode(' | ')),
                    'url' => route('admin.associations.show', $association),
                ])
                ->all();

        return $this->group('Associations', 'Association records', $items, route('admin.associations.index', ['search' => $term]));
    }

    private function group(string $title, string $description, array $items, string $viewAllUrl): array
    {
        return [
            'title' => $title,
            'description' => $description,
            'count' => count($items),
            'items' => $items,
            'viewAllUrl' => $viewAllUrl,
        ];
    }
}
