<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AktivitasStatus;
use App\Models\Aktivitas;
use App\Repositories\Interfaces\AktivitasRepositoryInterface;
use App\Services\Interfaces\AktivitasServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Service layer for Aktivitas (Daily Activity) business logic.
 */
class AktivitasService extends Service implements AktivitasServiceInterface
{
    public function __construct(
        private readonly AktivitasRepositoryInterface $aktivitasRepository,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function getPaginated(array $filters = []): LengthAwarePaginator
    {
        return $this->aktivitasRepository->search(
            keyword: $filters['search'] ?? null,
            tanggal: $filters['tanggal'] ?? null,
            status: $filters['status'] ?? null,
            periodeId: isset($filters['periode_id']) ? (int) $filters['periode_id'] : null,
            guruId: isset($filters['guru_id']) ? (int) $filters['guru_id'] : null,
            siswaId: isset($filters['siswa_id']) ? (int) $filters['siswa_id'] : null,
            sortBy: $filters['sort_by'] ?? 'tanggal',
            sortDirection: $filters['sort_direction'] ?? 'desc',
            perPage: isset($filters['per_page']) ? (int) $filters['per_page'] : 15,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function findOrFail(int $id): Aktivitas
    {
        /** @var Aktivitas|null $aktivitas */
        $aktivitas = $this->aktivitasRepository->find($id);

        if ($aktivitas === null) {
            throw new ModelNotFoundException('Aktivitas tidak ditemukan.');
        }

        return $aktivitas;
    }

    /**
     * {@inheritDoc}
     */
    public function store(array $data): Aktivitas
    {
        /** @var Aktivitas $aktivitas */
        $aktivitas = $this->transaction(function () use ($data): Model {
            // Handle photo upload
            $fotoPath = null;
            if (isset($data['foto_kegiatan']) && $data['foto_kegiatan'] instanceof \Illuminate\Http\UploadedFile) {
                $fotoPath = $data['foto_kegiatan']->store('aktivitas', 'public');
            }

            return $this->aktivitasRepository->create([
                'penempatan_pkl_id' => $data['penempatan_pkl_id'],
                'tanggal' => $data['tanggal'],
                'jam_mulai' => $data['jam_mulai'] ?? null,
                'jam_selesai' => $data['jam_selesai'] ?? null,
                'judul' => $data['judul'],
                'deskripsi' => $data['deskripsi'] ?? '',
                'hasil' => $data['hasil'] ?? null,
                'kendala' => $data['kendala'] ?? null,
                'solusi' => $data['solusi'] ?? null,
                'foto_kegiatan' => $fotoPath,
                'status' => AktivitasStatus::DRAFT->value,
            ]);
        });

        return $aktivitas;
    }

    /**
     * {@inheritDoc}
     */
    public function update(Aktivitas $aktivitas, array $data): Aktivitas
    {
        /** @var Aktivitas $updated */
        $updated = $this->transaction(function () use ($aktivitas, $data): Model {
            $updateData = [
                'tanggal' => $data['tanggal'],
                'jam_mulai' => $data['jam_mulai'] ?? null,
                'jam_selesai' => $data['jam_selesai'] ?? null,
                'judul' => $data['judul'],
                'deskripsi' => $data['deskripsi'] ?? '',
                'hasil' => $data['hasil'] ?? null,
                'kendala' => $data['kendala'] ?? null,
                'solusi' => $data['solusi'] ?? null,
            ];

            // Handle photo upload (replace existing)
            if (isset($data['foto_kegiatan']) && $data['foto_kegiatan'] instanceof \Illuminate\Http\UploadedFile) {
                // Delete old photo
                if ($aktivitas->foto_kegiatan) {
                    Storage::disk('public')->delete($aktivitas->foto_kegiatan);
                }
                $updateData['foto_kegiatan'] = $data['foto_kegiatan']->store('aktivitas', 'public');
            }

            return $this->aktivitasRepository->update($aktivitas, $updateData);
        });

        return $updated;
    }

/**
     * {@inheritDoc}
     */
public function destroy(Aktivitas $aktivitas): bool
    {
        return $this->aktivitasRepository->delete($aktivitas);
    }

    /**
     * {@inheritDoc}
     */
    public function restore(Aktivitas $aktivitas): bool
    {
        return $this->aktivitasRepository->restore($aktivitas);
    }

    /**
     * {@inheritDoc}
     */
    public function forceDelete(Aktivitas $aktivitas): bool
    {
        // Delete associated photo
        if ($aktivitas->foto_kegiatan) {
            Storage::disk('public')->delete($aktivitas->foto_kegiatan);
        }

        return $this->aktivitasRepository->forceDelete($aktivitas);
    }

    /**
     * {@inheritDoc}
     */
    public function getSiswaAktivitasPaginated(int $siswaId, array $filters = []): LengthAwarePaginator
    {
        return $this->aktivitasRepository->getBySiswaPaginated(
            siswaId: $siswaId,
            tanggal: $filters['tanggal'] ?? null,
            status: $filters['status'] ?? null,
            sortBy: $filters['sort_by'] ?? 'tanggal',
            sortDirection: $filters['sort_direction'] ?? 'desc',
            perPage: isset($filters['per_page']) ? (int) $filters['per_page'] : 15,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getGuruAktivitasPaginated(int $guruId, array $filters = []): LengthAwarePaginator
    {
        return $this->aktivitasRepository->getByGuruPaginated(
            guruId: $guruId,
            keyword: $filters['search'] ?? null,
            tanggal: $filters['tanggal'] ?? null,
            status: $filters['status'] ?? null,
            periodeId: isset($filters['periode_id']) ? (int) $filters['periode_id'] : null,
            sortBy: $filters['sort_by'] ?? 'tanggal',
            sortDirection: $filters['sort_direction'] ?? 'desc',
            perPage: isset($filters['per_page']) ? (int) $filters['per_page'] : 15,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getDudiAktivitasPaginated(int $dudiId, array $filters = []): LengthAwarePaginator
    {
        return $this->aktivitasRepository->getByDudiPaginated(
            dudiId: $dudiId,
            keyword: $filters['search'] ?? null,
            tanggal: $filters['tanggal'] ?? null,
            status: $filters['status'] ?? null,
            periodeId: isset($filters['periode_id']) ? (int) $filters['periode_id'] : null,
            sortBy: $filters['sort_by'] ?? 'tanggal',
            sortDirection: $filters['sort_direction'] ?? 'desc',
            perPage: isset($filters['per_page']) ? (int) $filters['per_page'] : 15,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function submit(Aktivitas $aktivitas): Aktivitas
    {
        /** @var Aktivitas $updated */
        $updated = $this->transaction(function () use ($aktivitas): Model {
            if ($aktivitas->status !== AktivitasStatus::DRAFT->value) {
                throw new \RuntimeException('Aktivitas sudah dikirim dan tidak dapat dikirim ulang.');
            }

            return $this->aktivitasRepository->update($aktivitas, [
                'status' => AktivitasStatus::MENUNGGU_VALIDASI->value,
            ]);
        });

        return $updated;
    }

    /**
     * {@inheritDoc}
     */
    public function validateAktivitas(Aktivitas $aktivitas, array $data): Aktivitas
    {
        /** @var Aktivitas $updated */
        $updated = $this->transaction(function () use ($aktivitas, $data): Model {
            if ($aktivitas->status !== AktivitasStatus::MENUNGGU_VALIDASI->value) {
                throw new \RuntimeException('Status aktivitas harus "Menunggu Validasi" untuk divalidasi.');
            }

            $status = $data['status'];
            if (!in_array($status, [AktivitasStatus::DISETUJUI->value, AktivitasStatus::DITOLAK->value], true)) {
                throw new \RuntimeException('Status validasi harus "Disetujui" atau "Ditolak".');
            }

            return $this->aktivitasRepository->update($aktivitas, [
                'status' => $status,
                'catatan_guru' => $data['catatan_guru'] ?? null,
                'validated_by' => Auth::id(),
                'validated_at' => now(),
            ]);
        });

        return $updated;
    }

    /**
     * {@inheritDoc}
     */
    public function hasCheckedInToday(int $penempatanPklId): bool
    {
        /** @var \App\Models\Absensi|null $absensi */
        $absensi = \App\Models\Absensi::query()
            ->where('penempatan_pkl_id', $penempatanPklId)
            ->whereDate('tanggal', now()->toDateString())
            ->whereNotNull('jam_masuk')
            ->first();

        return $absensi !== null;
    }
}

