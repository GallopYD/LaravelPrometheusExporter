<?php
namespace GallopYD\PrometheusExporter\Middleware;

use Illuminate\Http\Request;
use Closure;
use Illuminate\Support\Facades\Route;
use GallopYD\PrometheusExporter\Contract\PrometheusExporterContract;

class RequestPerRoute
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
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     *
     * @throws \Prometheus\Exception\MetricsRegistrationException
     */
    public function handle(Request $request, Closure $next)
    {
        $start = microtime(true);

        /** @var \Illuminate\Http\Response $response */
        $response = $next($request);

        $durationMilliseconds = (microtime(true) - $start) * 1000.0;

        $requestUri = $request->getRequestUri();
        $method = $request->getMethod();
        $status = $response->getStatusCode();

        $this->requestCountMetric($requestUri, $method, $status);
        $this->requestLatencyMetric($requestUri, $method, $status, $durationMilliseconds);

        return $response;
    }

    /**
     * @param string $requestUri
     * @param string $method
     * @param int $status
     * @throws \Prometheus\Exception\MetricsRegistrationException
     */
    private function requestCountMetric(string $requestUri, string $method, int $status)
    {
        $this->prometheusExporter->incCounter(
            'requests_total',
            'the number of http requests',
            config('prometheus_exporter.namespace_http'),
            [
                'request_uri',
                'method',
                'status_code'
            ],
            [
                $requestUri,
                $method,
                $status
            ]
        );
    }

    /**
     * @param string $requestUri
     * @param string $method
     * @param int $status
     * @param int $duration
     * @throws \Prometheus\Exception\MetricsRegistrationException
     */
    private function requestLatencyMetric(string $requestUri, string $method, int $status, int $duration)
    {
        $bucketsPerRoute = null;

        if ($bucketsPerRouteConfig = config('prometheus-exporter.buckets_per_route')) {
            $bucketsPerRoute = array_get($bucketsPerRouteConfig, $requestUri);
        }

        $this->prometheusExporter->setHistogram(
            'requests_latency_milliseconds',
            'duration of requests',
            $duration,
            config('prometheus_exporter.namespace_http'),
            [
                'request_uri',
                'method',
                'status_code'
            ],
            [
                $requestUri,
                $method,
                $status
            ],
            $bucketsPerRoute
        );
    }
}
