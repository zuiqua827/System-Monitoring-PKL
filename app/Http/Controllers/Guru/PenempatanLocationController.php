<?php

declare(strict_types=1);

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePenempatanLocationRequest;
use App\Models\Guru;
use App\Models\PenempatanPKL;
use App\Models\User;
use App\Services\Interfaces\PenempatanPKLServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Allows Guru Pembimbing to configure WFO/WFH work mode and geofence settings
 * strictly for students under their own guidance.
 */
class PenempatanLocationController extends Controller
{
    public function __construct(
        private readonly PenempatanPKLServiceInterface $penempatanPklService,
    ) {}

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        /** @var Guru|null $guru */
        $guru = $user->guru;

        if ($guru === null) {
            abort(403, 'Data guru tidak ditemukan.');
        }

        $penempatanPkls = $this->penempatanPklService->getPaginated(
            keyword: is_string($request->query('search')) ? $request->query('search') : null,
            sortBy: 'created_at',
            sortDirection: 'desc',
            perPage: 15,
            guruId: $guru->id,
            status: is_string($request->query('status')) ? $request->query('status') : null,
        );

        return view('guru.penempatan-pkl.index', compact('penempatanPkls'));
    }

    public function edit(PenempatanPKL $penempatanPkl): View
    {
        $this->authorize('manageLocation', $penempatanPkl);

        $penempatanPkl->load(['siswa.kelas', 'guru', 'dudi', 'periodePKL', 'locationLogs']);

        return view('guru.penempatan-pkl.location', compact('penempatanPkl'));
    }

    public function update(UpdatePenempatanLocationRequest $request, PenempatanPKL $penempatanPkl): RedirectResponse
    {
        $this->authorize('manageLocation', $penempatanPkl);

        /** @var User $actor */
        $actor = $request->user();

        $this->penempatanPklService->updateLocationSettings(
            penempatanPkl: $penempatanPkl,
            actor: $actor,
            data: $request->validated(),
            ipAddress: $request->ip(),
        );

        return redirect()
            ->route('guru.penempatan-pkl.location.edit', $penempatanPkl)
            ->with('success', 'Pengaturan lokasi absensi (WFO/WFH) berhasil disimpan.');
    }
}
