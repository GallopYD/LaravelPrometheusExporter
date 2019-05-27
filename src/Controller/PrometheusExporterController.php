<?php

namespace GallopYD\PrometheusExporter\Controller;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Prometheus\RenderTextFormat;
use Illuminate\Support\Facades\Validator;
use GallopYD\PrometheusExporter\PrometheusExporter;

class PrometheusExporterController extends Controller
{
    /**
     * @var PrometheusExporter
     */
    protected $prometheusExporter;

    /**
     * PrometheusExporterController constructor.
     *
     * @param PrometheusExporter $prometheusExporter
     */
    public function __construct(PrometheusExporter $prometheusExporter)
    {
        $this->prometheusExporter = $prometheusExporter;
    }

    /**
     * get metrics
     *
     * Expose metrics for prometheus
     *
     * @return Response
     */
    public function index()
    {
        $renderer = new RenderTextFormat();

        return Response::create(
            $renderer->render($this->prometheusExporter->getMetricFamilySamples())
        )->header('Content-Type', RenderTextFormat::MIME_TYPE);
    }

    /**
     * store metric
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $data = [
            'metric' => $request->get('metric'),
            'name' => $request->get('name'),
            'help' => $request->get('help'),
            'duration' => $request->get('duration'),
            'namespace' => $request->get('namespace'),
            'label_keys' => $request->get('label_keys'),
            'label_values' => $request->get('label_values'),
            'buckets' => $request->get('buckets', null),
        ];
        Validator::make($data, [
            'metric' => 'required|string|in:histogram,counter',
            'name' => 'required|string',
            'help' => 'required|string',
            'namespace' => 'required|string',
            'label_keys' => 'required|array',
            'label_values' => 'required|array',
        ]);

        switch ($data['metric']) {
            case 'histogram':
                $this->prometheusExporter->setHistogram(
                    $data['name'],
                    $data['help'],
                    $data['duration'],
                    $data['namespace'],
                    $data['label_keys'],
                    $data['label_values'],
                    $data['buckets']
                );
                break;
            case 'counter':
                $this->prometheusExporter->incCounter(
                    $data['name'],
                    $data['help'],
                    $data['namespace'],
                    $data['label_keys'],
                    $data['label_values']
                );
                break;
        }
        return response()->json([
            'message' => 'success'
        ]);
    }
}
