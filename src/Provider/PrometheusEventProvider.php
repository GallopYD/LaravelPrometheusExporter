<?php

namespace GallopYD\PrometheusExporter\Provider;

use GallopYD\PrometheusExporter\Contract\PrometheusExporterContract;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class PrometheusEventProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        $watchers = config('prometheus-exporter.event_watchers');
        foreach ($watchers as $class => $enable) {
            if (!$enable) {
                continue;
            }
            (new $class(app(PrometheusExporterContract::class)))->watch();
        }

        parent::boot();
    }
}
