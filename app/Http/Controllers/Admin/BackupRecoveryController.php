<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Farmer;
use App\Models\User;
use App\Services\Audit\AuditTrailService;
use App\Services\Backup\BackupRecoveryService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupRecoveryController extends Controller
{
    public function __construct(
        private readonly BackupRecoveryService $backupRecoveryService,
        private readonly AuditTrailService $auditTrailService,
    ) {
        $this->middleware('role.in:' . User::ROLE_ADMIN);
    }

    public function index(): InertiaResponse
    {
        $snapshots = collect($this->backupRecoveryService->listSnapshots());

        return Inertia::render('Admin/Backups/Index', [
            'summary' => [
                'total' => $snapshots->count(),
                'databaseBackups' => $snapshots->where('type', 'database')->count(),
                'exportSnapshots' => $snapshots->where('type', 'export-snapshots')->count(),
                'restorePoints' => $snapshots->where('type', 'restore-points')->count(),
            ],
            'snapshots' => $snapshots->map(fn (array $snapshot): array => [
                'key' => $snapshot['key'],
                'label' => $snapshot['label'],
                'type' => $snapshot['type'],
                'createdAt' => filled($snapshot['created_at']) ? Carbon::parse($snapshot['created_at'])->format('M d, Y h:i A') : null,
                'requestedBy' => $this->actorName($snapshot['requested_by'] ?? null),
                'fileName' => $snapshot['file_name'],
                'fileSize' => $snapshot['file_size'],
                'checksum' => $snapshot['checksum'],
                'exists' => (bool) $snapshot['exists'],
                'metadata' => $snapshot['metadata'] ?? [],
                'downloadUrl' => route('admin.backups.download', $snapshot['key']),
            ])->values()->all(),
            'urls' => [
                'databaseBackup' => route('admin.backups.database.store'),
                'farmerSnapshot' => route('admin.backups.farmers.snapshot.store'),
            ],
        ]);
    }

    public function storeDatabaseBackup(Request $request): RedirectResponse
    {
        $manifest = $this->backupRecoveryService->createDatabaseSnapshot($request->user()?->id);

        $this->auditTrailService->record(
            'backups',
            'database_backup_created',
            'Created a database backup snapshot.',
            $request->user(),
            null,
            $this->auditTrailService->activityMetadata(
                AuditTrailService::ACTION_PROCESSED,
                'Database backup',
                [
                    'backup_key' => $manifest['key'],
                    'file_name' => $manifest['file_name'],
                    'file_size' => $manifest['file_size'],
                    'checksum' => $manifest['checksum'],
                ]
            )
        );

        return back()->with('success', 'Database backup snapshot created.');
    }

    public function storeFarmerSnapshot(Request $request): RedirectResponse
    {
        $farmers = Farmer::query()
            ->with(['profile', 'barangay', 'association', 'memberType'])
            ->when($request->filled('status'), function (Builder $query) use ($request): void {
                $status = strtolower((string) $request->input('status'));

                if ($status === 'active') {
                    $query->whereNull('inactive_at')->where('membership_status', 'active');
                    return;
                }

                if (in_array($status, ['inactive', 'deceased'], true)) {
                    $query->whereNotNull('inactive_at');
                    return;
                }

                if ($status === 'pending') {
                    $query->whereNull('inactive_at')->where('membership_status', '!=', 'active');
                }
            })
            ->when($request->filled('barangay_id'), fn (Builder $query) => $query->where('barangay_id', $request->input('barangay_id')))
            ->when($request->filled('member_type_id'), fn (Builder $query) => $query->where('member_type_id', $request->input('member_type_id')))
            ->get();

        $manifest = $this->backupRecoveryService->createFarmerExportSnapshot(
            $farmers,
            $request->only(['status', 'barangay_id', 'member_type_id']),
            $request->user()?->id,
        );

        $this->auditTrailService->record(
            'backups',
            'farmer_export_snapshot_created',
            'Created a farmer registry export snapshot.',
            $request->user(),
            null,
            $this->auditTrailService->activityMetadata(
                AuditTrailService::ACTION_PROCESSED,
                'Export snapshot',
                [
                    'snapshot_key' => $manifest['key'],
                    'record_count' => $manifest['metadata']['record_count'] ?? null,
                ]
            )
        );

        return back()->with('success', 'Farmer export snapshot created.');
    }

    public function download(string $key): StreamedResponse|RedirectResponse
    {
        $snapshot = $this->backupRecoveryService->getSnapshot($key);

        if (! $snapshot || ! filled($snapshot['file_path'] ?? null) || ! Storage::disk('local')->exists($snapshot['file_path'])) {
            return redirect()
                ->route('admin.backups.index')
                ->with('error', 'The selected backup file is no longer available.');
        }

        return Storage::disk('local')->download($snapshot['file_path'], $snapshot['file_name'] ?? basename($snapshot['file_path']));
    }

    public function recoverFarmer(Request $request, Farmer $farmer): RedirectResponse
    {
        $validated = $request->validate([
            'snapshot_key' => ['required', 'string'],
        ]);

        $restored = $this->backupRecoveryService->restoreFarmerFromSnapshot($validated['snapshot_key'], $farmer);

        if (! $restored) {
            return back()->with('error', 'The selected restore point did not contain this farmer record.');
        }

        $this->auditTrailService->record(
            'backups',
            'farmer_record_recovered',
            'Recovered a farmer record from a restore point.',
            $request->user(),
            $farmer->fresh('profile'),
            $this->auditTrailService->activityMetadata(
                AuditTrailService::ACTION_PROCESSED,
                'Farmer record recovery',
                [
                    'snapshot_key' => $validated['snapshot_key'],
                    'farmer_code' => $farmer->farmer_code,
                ]
            )
        );

        return redirect()
            ->route('admin.farmers.show', $farmer)
            ->with('success', 'Farmer record restored from snapshot.');
    }

    private function actorName(mixed $userId): string
    {
        if (! $userId) {
            return 'System';
        }

        return User::query()->whereKey($userId)->value('name') ?? 'User #' . $userId;
    }
}
