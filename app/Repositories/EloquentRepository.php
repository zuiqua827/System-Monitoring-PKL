<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\Interfaces\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 *
 * @implements BaseRepositoryInterface<TModel>
 */
abstract class EloquentRepository implements BaseRepositoryInterface
{
    /**
     * @param TModel $model
     */
    public function __construct(protected readonly Model $model)
    {
    }

    /**
     * @return Collection<int, TModel>
     */
    public function all(array $columns = ['*']): Collection
    {
        return $this->newQuery()->get($columns);
    }

    /**
     * @return LengthAwarePaginator<int, TModel>
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->newQuery()->paginate(
            perPage: min(max($perPage, 1), 100),
            columns: $columns,
        );
    }

    /**
     * @return TModel|null
     */
    public function find(int|string $id, array $columns = ['*']): ?Model
    {
        return $this->newQuery()->find($id, $columns);
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @return TModel
     */
    public function create(array $attributes): Model
    {
        return $this->model->newQuery()->create($attributes);
    }

    /**
     * @param TModel $model
     * @param array<string, mixed> $attributes
     *
     * @return TModel
     */
    public function update(Model $model, array $attributes): Model
    {
        $model->forceFill($attributes);
        $model->save();

        return $model->refresh();
    }

    /**
     * @param TModel $model
     */
    public function delete(Model $model): bool
    {
        return (bool) $model->delete();
    }

    /**
     * @return Builder<TModel>
     */
    protected function newQuery(): Builder
    {
        return $this->model->newQuery();
    }
}
