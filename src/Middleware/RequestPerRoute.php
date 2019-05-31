<?php

namespace GallopYD\PrometheusExporter\Middleware;

use GallopYD\PrometheusExporter\Utils\CommonUtil;
use GallopYD\PrometheusExporter\Utils\DeviceUtil;
use Illuminate\Http\Request;
use Closure;
use GallopYD\PrometheusExporter\Contract\PrometheusExporterContract;
use Tymon\JWTAuth\Facades\JWTAuth;

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

        $this->recordRequest($request, $response, $start);
        $this->recordUserData($request, $response);

        return $response;
    }

    private function recordRequest($request, $response, $start)
    {
        $buckets = config('prometheus-exporter.buckets_per_route');
        $durationMilliseconds = (microtime(true) - $start) * 1000.0;
        $labelKeys = config('prometheus-exporter.http_label_keys');
        $labelValues = $this->getLabelValue($request, $response, $labelKeys);

        $this->prometheusExporter->incCounter(
            'requests_total',
            'the number of http requests',
            config('prometheus-exporter.namespace_http'),
            $labelKeys,
            $labelValues
        );
        $this->prometheusExporter->setHistogram(
            'requests_latency_milliseconds',
            'duration of requests',
            $durationMilliseconds,
            config('prometheus-exporter.namespace_http'),
            $labelKeys,
            $labelValues,
            $buckets
        );
    }

    private function recordUserData($request, $response)
    {
        $request_uri = $request->route()->uri();
        $method = $request->getMethod();
        $status_code = $response->getStatusCode();
        $user_watchers = config('prometheus-exporter.user_watchers');
        if ($user_watchers && $status_code == 200) {
            foreach ($user_watchers as $key => $value) {
                if (isset($value[$request_uri]) && ($value[$request_uri] == $method || $value[$request_uri] == 'ANY')) {
                    $labelKeys = config('prometheus-exporter.user_label_keys');
                    $labelValues = $this->getLabelValue($request, $response, $labelKeys);

                    $this->prometheusExporter->incCounter(
                        "{$key}_total",
                        "the number of {$key}",
                        config('prometheus-exporter.namespace'),
                        $labelKeys,
                        $labelValues
                    );
                }
            }

        }
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
                    $status_code = $response->getStatusCode();
                    array_push($labelValues, $status_code);
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
                case 'ip':
                    $ip = CommonUtil::getIp();
                    array_push($labelValues, $ip);
                    break;
                case 'user_id':
                    $user_id = $this->getUserId($response);
                    array_push($labelValues, $user_id);
                    break;
                default:
                    array_push($labelValues, $request->$labelKey);
                    break;

            }
        }
        return $labelValues;
    }

    private function getUserId($response)
    {
        try {
            $data = json_decode($response->getContent(), true);
            if (isset($data['data']['token'])) {
                $token = str_replace('Bearer ', '', $data['data']['token']);
            } else {
                $token = JWTAuth::getToken();
            }
            $user = JWTAuth::toUser($token);
            $user_id = $user->id;
        } catch (\Exception $exception) {
            $user_id = null;
        }
        return $user_id;
    }
}
