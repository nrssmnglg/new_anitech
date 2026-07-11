<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInternalNoteRequest;
use App\Models\Farmer;
use App\Models\MembershipApplication;
use App\Models\Query;
use App\Models\RenewalRequest;
use App\Models\User;
use App\Services\Audit\AuditTrailService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;

class InternalNoteController extends Controller
{
    public function __construct(
        private readonly AuditTrailService $auditTrailService,
    ) {
        $this->middleware('role.in:' . User::ROLE_ADMIN . ',' . User::ROLE_STAFF);
    }

    public function storeForApplication(StoreInternalNoteRequest $request, MembershipApplication $membershipApplication): RedirectResponse
    {
        return $this->storeNote(
            $request->validated('body'),
            $membershipApplication,
            'membership_applications',
            'Added an internal note to a membership application.',
            route('admin.membership-applications.show', $membershipApplication)
        );
    }

    public function storeForRenewal(StoreInternalNoteRequest $request, RenewalRequest $renewal): RedirectResponse
    {
        return $this->storeNote(
            $request->validated('body'),
            $renewal,
            'renewals',
            'Added an internal note to a renewal record.',
            route('admin.renewals.show', $renewal)
        );
    }

    public function storeForQuery(StoreInternalNoteRequest $request, Query $query): RedirectResponse
    {
        return $this->storeNote(
            $request->validated('body'),
            $query,
            'queries',
            'Added an internal note to an inquiry thread.',
            route('admin.queries.show', $query)
        );
    }

    public function storeForFarmer(StoreInternalNoteRequest $request, Farmer $farmer): RedirectResponse
    {
        return $this->storeNote(
            $request->validated('body'),
            $farmer,
            'farmers',
            'Added an internal note to a farmer record.',
            route('admin.farmers.show', $farmer)
        );
    }

    private function storeNote(string $body, Model $record, string $module, string $description, string $redirectTo): RedirectResponse
    {
        $note = $record->internalNotes()->create([
            'body' => $body,
            'created_by' => auth()->id(),
        ]);

        $this->auditTrailService->record(
            $module,
            'internal_note_added',
            $description,
            auth()->user(),
            $record,
            $this->auditTrailService->activityMetadata(
                AuditTrailService::ACTION_EDITED,
                'Internal note',
                [
                    'note_id' => $note->id,
                    'excerpt' => str($body)->limit(120)->value(),
                ]
            )
        );

        return redirect($redirectTo)->with('success', 'Internal note saved.');
    }
}
