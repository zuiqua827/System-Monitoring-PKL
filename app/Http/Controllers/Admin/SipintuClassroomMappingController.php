<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Interfaces\SipintuClassroomMappingServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Super Admin controller for mapping SiPintu classroom_ids to local kelas.
 *
 * Thin controller: delegates all business logic to the service layer.
 * Access is enforced by the `role:Super Admin` route middleware.
 */
class SipintuClassroomMappingController extends Controller
{
    public function __construct(
        private readonly SipintuClassroomMappingServiceInterface $mappingService,
    ) {}

    /**
     * Display the classroom mapping page.
     */
    public function index(): View
    {
        $data = $this->mappingService->getDashboardData();

        return view('admin.sipintu-classroom-mapping.index', array_merge([
            'classrooms' => $data['classrooms'],
            'mappings' => $data['mappings'],
            'kelasOptions' => $data['kelasOptions'],
            'connected' => $data['connected'],
        ]));
    }

    /**
     * Save a single classroom_id → kelas mapping.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'classroom_id' => ['required', 'integer', 'min:1'],
            'kelas_id' => ['required', 'integer', 'exists:kelas,id'],
        ]);

        try {
            /** @var User $user */
            $user = $request->user();

            $this->mappingService->saveMapping(
                classroomId: (int) $validated['classroom_id'],
                kelasId: (int) $validated['kelas_id'],
                userId: $user->id,
            );

            return redirect()
                ->route('admin.sipintu-classroom-mapping.index')
                ->with('success', "Mapping classroom_id {$validated['classroom_id']} berhasil disimpan.");
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('admin.sipintu-classroom-mapping.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Apply all saved mappings to local students.
     */
    public function apply(): RedirectResponse
    {
        $stats = $this->mappingService->applyMappings();

        return redirect()
            ->route('admin.sipintu-classroom-mapping.index')
            ->with('success', "Mapping diterapkan: {$stats['updated']} diperbarui, {$stats['skipped']} dilewati, {$stats['failed']} gagal.");
    }
}
