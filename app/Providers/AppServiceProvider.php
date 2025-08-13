<?php

namespace App\Providers;

use App\Models\Mapping;
use App\Observers\MappingObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Mapping::observe(MappingObserver::class);
    }
}