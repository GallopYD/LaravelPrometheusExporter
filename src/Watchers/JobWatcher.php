<?php

namespace GallopYD\PrometheusExporter\Watchers;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Redis;

class JobWatcher extends Watcher
{

    public function watch()
    {
        Event::listen(JobProcessing::class, function ($event) {
            $command = $this->getCommandData($event);
            $command['start'] = time();
            Redis::set($command['job_id'], json_encode($command));
        });

        Event::listen(JobProcessed::class, function ($event) {
            $command = $this->getCommandData($event);
            $end = time();
            if ($data = Redis::get($command['job_id'])) {
                $data = json_decode($data, true);
                $start = $data['start'];
            } else {
                $start = $end = -1;
            }
            $this->record($command, $start, $end);
        });

        Event::listen(JobFailed::class, function ($event) {
            $command = $this->getCommandData($event);
            $end = time();
            if ($data = Redis::get($command['job_id'])) {
                $data = json_decode($data, true);
                $start = $data['start'];
            } else {
                $start = $end = 0;
            }
            $this->record($command, $start, $end);
        });
    }

    private function record($command, $start, $end)
    {
        $durationMilliseconds = ($end - $start) * 1000.0;
        unset($command['job_id']);

        $labelKeys = array_keys($command);
        $labelValues = array_values($command);

        $this->countMetric(
            'jobs_total',
            'the number of jobs',
            $labelKeys,
            $labelValues);

        $this->latencyMetric(
            'jobs_latency_milliseconds',
            'duration of jobs',
            $labelKeys,
            $labelValues,
            $durationMilliseconds);
    }

    private function getCommandData($event)
    {
        $job = json_decode($event->job->getRawBody(), true);
        $job_id = $event->job->getJobId();
        $command = $job['data']['commandName'];
        return compact('job_id', 'command');
    }
}
