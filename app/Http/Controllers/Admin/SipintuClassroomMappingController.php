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

        return view('admin.sipintu-classroom-mapping.index', [
            'classrooms' => $data['classrooms'],
            'mappings' => $data['mappings'],
            'mappingHealth' => $data['mappingHealth'],
            'kelasOptions' => $data['kelasOptions'],
            'summary' => $data['summary'],
            'connected' => $data['connected'],
        ]);
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
                ->with('success', "Mapping classroom_id {$validated['classroom_id']} berhasil disimpan. Jalankan preview/sinkronisasi di menu Sinkronisasi SiPintu untuk memproses data siswa.");
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('admin.sipintu-classroom-mapping.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Remove a saved classroom mapping (e.g. invalid/obsolete mapping).
     */
    public function destroy(int $mapping): RedirectResponse
    {
        $deleted = $this->mappingService->deleteMapping($mapping);

        return redirect()
            ->route('admin.sipintu-classroom-mapping.index')
            ->with($deleted ? 'success' : 'error', $deleted
                ? 'Pemetaan kelas berhasil dihapus.'
                : 'Pemetaan kelas tidak ditemukan.');
    }

    /**
     * Auto-map unmapped SiPintu classrooms that strictly match local Kelas (nama, tingkat, jurusan).
     */
    public function autoMap(Request $request): RedirectResponse
    {
        try {
            /** @var User $user */
            $user = $request->user();

            $stats = $this->mappingService->autoMapExactMatches($user->id);

            return redirect()
                ->route('admin.sipintu-classroom-mapping.index')
                ->with('success', "Auto-map selesai: {$stats['mapped']} dipetakan otomatis, {$stats['already_mapped']} sudah terpetakan, {$stats['skipped_unmatched']} tidak cocok eksak (perlu mapping manual), {$stats['skipped_conflict']} konflik/ambigu dilewati.");
        } catch (\Throwable $e) {
            return redirect()
                ->route('admin.sipintu-classroom-mapping.index')
                ->with('error', 'Auto-map gagal mengambil data dari SiPintu: '.$e->getMessage());
        }
    }

    /**
     * Apply all saved mappings to existing local students.
     */
    public function apply(): RedirectResponse
    {
        $stats = $this->mappingService->applyMappings();

        return redirect()
            ->route('admin.sipintu-classroom-mapping.index')
            ->with('success', "Mapping diterapkan: {$stats['updated']} diperbarui, {$stats['skipped']} dilewati, {$stats['failed']} gagal.");
    }
}
