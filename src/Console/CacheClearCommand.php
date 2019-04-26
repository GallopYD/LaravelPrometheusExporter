<?php

namespace GallopYD\PrometheusExporter\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class CacheClearCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prometheus:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prometheus cache clear';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }


    public function handle()
    {
        Redis::del('PROMETHEUS_counter_METRIC_KEYS');
        Redis::del('PROMETHEUS_histogram_METRIC_KEYS');
        Redis::del('PROMETHEUS_:histogram:http_requests_latency_milliseconds');
        Redis::del('PROMETHEUS_:counter:http_requests_total');

        apc_clear_cache('user');
    }
}