<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Interfaces\JurusanServiceInterface;
use App\Services\Interfaces\KelasServiceInterface;
use App\Services\Interfaces\PeriodePKLServiceInterface;
use App\Services\Interfaces\UserAuthenticationServiceInterface;
use App\Services\Interfaces\UserProfileServiceInterface;
use App\Services\JurusanService;
use App\Services\KelasService;
use App\Services\PeriodePKLService;
use App\Services\UserAuthenticationService;
use App\Services\UserProfileService;
use Illuminate\Support\ServiceProvider;

class ServiceServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    private array $services = [
        JurusanServiceInterface::class => JurusanService::class,
        KelasServiceInterface::class => KelasService::class,
        PeriodePKLServiceInterface::class => PeriodePKLService::class,
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
