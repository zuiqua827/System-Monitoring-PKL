<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ServiceServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    private array $services = [
        //
    ];

    public function register(): void
    {
        foreach ($this->services as $contract => $implementation) {
            $this->app->bind($contract, $implementation);
        }
    }
}
