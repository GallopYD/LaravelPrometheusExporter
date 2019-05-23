<?php

namespace GallopYD\PrometheusExporter\Watchers;

use GallopYD\PrometheusExporter\Contract\PrometheusExporterContract;

class Watcher
{

    /** @var PrometheusExporterContract */
    private $prometheusExporter;

    /**
     * RequestPerRoute constructor.
     * @param PrometheusExporterContract $prometheusExporter
     */
    public function __construct(PrometheusExporterContract $prometheusExporter)
    {
        $this->prometheusExporter = $prometheusExporter;
    }

    /**
     * @param $name
     * @param $help
     * @param array $labelKeys
     * @param array $labelValues
     * @throws \Prometheus\Exception\MetricsRegistrationException
     */
    public function countMetric($name, $help, array $labelKeys, array $labelValues)
    {
        $this->prometheusExporter->incCounter(
            $name,
            $help,
            config('prometheus-exporter.namespace'),
            $labelKeys,
            $labelValues
        );
    }

    /**
     * @param $name
     * @param $help
     * @param array $labelKeys
     * @param array $labelValues
     * @param int $duration
     * @throws \Prometheus\Exception\MetricsRegistrationException
     */
    public function latencyMetric($name, $help, array $labelKeys, array $labelValues, int $duration)
    {
        $bucketsPerRoute = null;

        $this->prometheusExporter->setHistogram(
            $name,
            $help,
            $duration,
            config('prometheus-exporter.namespace'),
            $labelKeys,
            $labelValues,
            $bucketsPerRoute
        );
    }

}
