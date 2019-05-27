<?php

Route::get(
    'prometheus/metrics',
    \GallopYD\PrometheusExporter\Controller\PrometheusExporterController::class . '@index'
);

Route::post(
    'prometheus/metrics',
    \GallopYD\PrometheusExporter\Controller\PrometheusExporterController::class . '@store'
);
