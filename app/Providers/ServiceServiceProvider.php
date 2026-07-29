<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Interfaces\AbsensiServiceInterface;
use App\Services\Interfaces\DudiServiceInterface;
use App\Services\Interfaces\GuruServiceInterface;
use App\Services\Interfaces\JurusanServiceInterface;
use App\Services\Interfaces\KelasServiceInterface;
use App\Services\Interfaces\PenempatanPKLServiceInterface;
use App\Services\Interfaces\PeriodePKLServiceInterface;
use App\Services\Interfaces\SiswaServiceInterface;
use App\Services\Interfaces\UserAuthenticationServiceInterface;
use App\Services\Interfaces\UserProfileServiceInterface;
use App\Services\AbsensiService;
use App\Services\DudiService;
use App\Services\GuruService;
use App\Services\JurusanService;
use App\Services\KelasService;
use App\Services\PenempatanPKLService;
use App\Services\PeriodePKLService;
use App\Services\SiswaService;
use App\Services\UserAuthenticationService;
use App\Services\UserProfileService;
use Illuminate\Support\ServiceProvider;

class ServiceServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    private array $services = [
        AbsensiServiceInterface::class => AbsensiService::class,
        DudiServiceInterface::class => DudiService::class,
        GuruServiceInterface::class => GuruService::class,
        JurusanServiceInterface::class => JurusanService::class,
        KelasServiceInterface::class => KelasService::class,
        PenempatanPKLServiceInterface::class => PenempatanPKLService::class,
        PeriodePKLServiceInterface::class => PeriodePKLService::class,
        SiswaServiceInterface::class => SiswaService::class,
        UserAuthenticationServiceInterface::class => UserAuthenticationService::class,
        UserProfileServiceInterface::class => UserProfileService::class,
    ];

    public function register(): void
    {
        foreach ($this->services as $contract => $implementation) {
            $this->app->bind($contract, $implementation);
        }
    }
}
