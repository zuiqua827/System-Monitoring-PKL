<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Interfaces\JurusanRepositoryInterface;
use App\Repositories\Interfaces\KelasRepositoryInterface;
use App\Repositories\Interfaces\PeriodePKLRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\JurusanRepository;
use App\Repositories\KelasRepository;
use App\Repositories\PeriodePKLRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    private array $repositories = [
        JurusanRepositoryInterface::class => JurusanRepository::class,
        KelasRepositoryInterface::class => KelasRepository::class,
        PeriodePKLRepositoryInterface::class => PeriodePKLRepository::class,
        UserRepositoryInterface::class => UserRepository::class,
    ];

    public function register(): void
    {
        foreach ($this->repositories as $contract => $implementation) {
            $this->app->bind($contract, $implementation);
        }
    }
}
