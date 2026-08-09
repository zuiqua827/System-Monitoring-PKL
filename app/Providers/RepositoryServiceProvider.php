<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\AbsensiRepository;
use App\Repositories\AktivitasRepository;
use App\Repositories\DashboardRepository;
use App\Repositories\DudiRepository;
use App\Repositories\GuruRepository;
use App\Repositories\Interfaces\AbsensiRepositoryInterface;
use App\Repositories\Interfaces\AktivitasRepositoryInterface;
use App\Repositories\Interfaces\DashboardRepositoryInterface;
use App\Repositories\Interfaces\DudiRepositoryInterface;
use App\Repositories\Interfaces\GuruRepositoryInterface;
use App\Repositories\Interfaces\JurusanRepositoryInterface;
use App\Repositories\Interfaces\KelasRepositoryInterface;
use App\Repositories\Interfaces\PenempatanPKLRepositoryInterface;
use App\Repositories\Interfaces\PenilaianRepositoryInterface;
use App\Repositories\Interfaces\PeriodePKLRepositoryInterface;
use App\Repositories\Interfaces\SiPintuRepositoryInterface;
use App\Repositories\Interfaces\SipintuClassroomMappingRepositoryInterface;
use App\Repositories\Interfaces\SipintuSyncLogRepositoryInterface;
use App\Repositories\Interfaces\SiswaRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\JurusanRepository;
use App\Repositories\KelasRepository;
use App\Repositories\PenempatanPKLRepository;
use App\Repositories\PenilaianRepository;
use App\Repositories\PeriodePKLRepository;
use App\Repositories\SiPintuRepository;
use App\Repositories\SipintuClassroomMappingRepository;
use App\Repositories\SipintuSyncLogRepository;
use App\Repositories\SiswaRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    private array $repositories = [
        AbsensiRepositoryInterface::class => AbsensiRepository::class,
        AktivitasRepositoryInterface::class => AktivitasRepository::class,
        DashboardRepositoryInterface::class => DashboardRepository::class,
        DudiRepositoryInterface::class => DudiRepository::class,
        GuruRepositoryInterface::class => GuruRepository::class,
        JurusanRepositoryInterface::class => JurusanRepository::class,
        KelasRepositoryInterface::class => KelasRepository::class,
        PenempatanPKLRepositoryInterface::class => PenempatanPKLRepository::class,
        PenilaianRepositoryInterface::class => PenilaianRepository::class,
        PeriodePKLRepositoryInterface::class => PeriodePKLRepository::class,
SiswaRepositoryInterface::class => SiswaRepository::class,
        SiPintuRepositoryInterface::class => SiPintuRepository::class,
        SipintuClassroomMappingRepositoryInterface::class => SipintuClassroomMappingRepository::class,
        SipintuSyncLogRepositoryInterface::class => SipintuSyncLogRepository::class,
        UserRepositoryInterface::class => UserRepository::class,
    ];

    public function register(): void
    {
        foreach ($this->repositories as $contract => $implementation) {
            $this->app->bind($contract, $implementation);
        }
    }
}
