<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dudi;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOperasionalRequest;
use App\Models\Dudi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * DUDI Operational Settings Controller.
 *
 * Allows DUDI users to configure their working hours and operational days.
 * The authenticated DUDI user can only edit their own settings.
 */
class OperasionalController extends Controller
{
    /**
     * Show the operational settings form.
     */
    public function edit(): View
    {
        $dudi = $this->getAuthenticatedDudi();

        // Format time values for the form inputs (H:i format)
        $jamMasuk = $this->formatTimeForInput($dudi->jam_masuk, '08:00');
        $batasTerlambat = $this->formatTimeForInput($dudi->batas_terlambat, '08:15');
        $batasSangatTerlambat = $this->formatTimeForInput($dudi->batas_sangat_terlambat, '09:00');
        $jamPulang = $this->formatTimeForInput($dudi->jam_pulang, '16:00');
        $hariOperasional = $dudi->getEffectiveHariOperasional();

        return view('dudi.operasional.edit', compact(
            'dudi',
            'jamMasuk',
            'batasTerlambat',
            'batasSangatTerlambat',
            'jamPulang',
            'hariOperasional',
        ));
    }

    /**
     * Update the operational settings.
     */
    public function update(UpdateOperasionalRequest $request): RedirectResponse
    {
        $dudi = $this->getAuthenticatedDudi();
        $data = $request->validated();

        $dudi->update([
            'jam_masuk'              => $data['jam_masuk'] . ':00',
            'batas_terlambat'        => $data['batas_terlambat'] . ':00',
            'batas_sangat_terlambat' => $data['batas_sangat_terlambat'] . ':00',
            'jam_pulang'             => $data['jam_pulang'] . ':00',
            'hari_operasional'       => $data['hari_operasional'],
        ]);

        return redirect()
            ->route('dudi.operasional.edit')
            ->with('success', 'Pengaturan operasional berhasil disimpan.');
    }

    /**
     * Get the DUDI model for the currently authenticated user.
     */
    private function getAuthenticatedDudi(): Dudi
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        /** @var Dudi|null $dudi */
        $dudi = $user->dudi;

        if ($dudi === null) {
            abort(403, 'Data DUDI tidak ditemukan.');
        }

        return $dudi;
    }

    /**
     * Format a time value for HTML time input (H:i).
     */
    private function formatTimeForInput(mixed $value, string $default): string
    {
        if ($value === null) {
            return $default;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('H:i');
        }

        // Handle string like "08:15:00"
        $str = (string) $value;
        if (preg_match('/^(\d{2}:\d{2})/', $str, $matches)) {
            return $matches[1];
        }

        return $default;
    }
}
