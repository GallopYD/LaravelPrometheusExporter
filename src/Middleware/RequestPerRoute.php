<?php

namespace GallopYD\PrometheusExporter\Middleware;

use GallopYD\DeviceUtil\DeviceUtil;
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

        $labelKeys = config('prometheus-exporter.label_keys');
        $labelValues = $this->getLabelValue($request, $response, $labelKeys);

        $this->requestCountMetric($labelKeys, $labelValues);
        $this->requestLatencyMetric($labelKeys, $labelValues, $durationMilliseconds);

        return $response;
    }

    /**
     * Get Label Value
     * @param $request
     * @param $response
     * @param $labelKeys
     * @return array
     */
    private function getLabelValue($request, $response, $labelKeys)
    {
        $labelValues = [];
        foreach ($labelKeys as $labelKey) {
            switch ($labelKey) {
                case 'app_name':
                    $app_name = config('app.name');
                    array_push($labelValues, $app_name);
                    break;
                case 'request_uri':
                    $request_uri = $request->route()->uri();
                    array_push($labelValues, $request_uri);
                    break;
                case 'method':
                    $method = $request->getMethod();
                    array_push($labelValues, $method);
                    break;
                case 'status_code':
                    $status = $response->getStatusCode();
                    array_push($labelValues, $status);
                    break;
                case 'client':
                    $user_agent = $request->server('HTTP_USER_AGENT');
                    $detector = DeviceUtil::getDeviceDetector($user_agent);
                    $client = $detector->getOs('name');
                    array_push($labelValues, $client);
                    break;
                case 'version':
                    $user_agent = $request->server('HTTP_USER_AGENT');
                    $detector = DeviceUtil::getDeviceDetector($user_agent);
                    $version = $detector->getOs('version');
                    array_push($labelValues, $version);
                    break;
                default:
                    array_push($labelValues, $request->$labelKey);
                    break;

            }
        }
        return $labelValues;
    }

    /**
     * @param array $labelKeys
     * @param array $labelValues
     * @throws \Prometheus\Exception\MetricsRegistrationException
     */
    private function requestCountMetric(array $labelKeys, array $labelValues)
    {
        $this->prometheusExporter->incCounter(
            'requests_total',
            'the number of http requests',
            config('prometheus-exporter.namespace_http'),
            $labelKeys,
            $labelValues
        );
    }

    /**
     * @param array $labelKeys
     * @param array $labelValues
     * @throws \Prometheus\Exception\MetricsRegistrationException
     */
    private function requestLatencyMetric(array $labelKeys, array $labelValues, int $duration)
    {
        $bucketsPerRoute = null;

//        if ($bucketsPerRouteConfig = config('prometheus-exporter.buckets_per_route')) {
//            $bucketsPerRoute = array_get($bucketsPerRouteConfig, $requestUri);
//        }

        $this->prometheusExporter->setHistogram(
            'requests_latency_milliseconds',
            'duration of requests',
            $duration,
            config('prometheus-exporter.namespace_http'),
            $labelKeys,
            $labelValues,
            $bucketsPerRoute
        );
    }
}
