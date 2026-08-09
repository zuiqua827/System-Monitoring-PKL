<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Guru;
use App\Repositories\Interfaces\GuruRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends EloquentRepository<Guru>
 */
class GuruRepository extends EloquentRepository implements GuruRepositoryInterface
{
    public function __construct(Guru $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritDoc}
     */
    public function findByNip(string $nip): ?Guru
    {
        /** @var Guru|null $guru */
        $guru = Guru::query()->withTrashed()->where('nip', $nip)->first();

        return $guru;
    }

    public function search(
        ?string $keyword = null,
        string $sortBy = 'nama',
        string $sortDirection = 'asc',
        int $perPage = 15,
    ): LengthAwarePaginator {
        $query = $this->newQuery();

        if ($keyword !== null && $keyword !== '') {
            $query->where(function ($q) use ($keyword): void {
                $q->where('nama', 'like', "%{$keyword}%")
                  ->orWhere('nip', 'like', "%{$keyword}%")
                  ->orWhere('no_hp', 'like', "%{$keyword}%")
                  ->orWhere('alamat', 'like', "%{$keyword}%");
            });
        }

        $allowedSorts = ['nama', 'nip', 'created_at'];
        $sortBy = in_array($sortBy, $allowedSorts, true) ? $sortBy : 'nama';
        $sortDirection = in_array($sortDirection, ['asc', 'desc'], true) ? $sortDirection : 'asc';

        return $query
            ->orderBy($sortBy, $sortDirection)
            ->paginate(min(max($perPage, 1), 100));
    }

    /**
     * {@inheritDoc}
     */
    public function allWithTrashed(): Collection
    {
        return $this->model->newQuery()->withTrashed()->get();
    }

    /**
     * {@inheritDoc}
     */
    public function restore(Guru $guru): bool
    {
        return (bool) $guru->restore();
    }

    /**
     * {@inheritDoc}
     */
    public function forceDelete(Guru $guru): bool
    {
        return (bool) $guru->forceDelete();
    }
}

