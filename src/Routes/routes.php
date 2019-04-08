<?php

Route::get(
    'triadev/pe/metrics',
    \GallopYD\PrometheusExporter\Controller\PrometheusExporterController::class . '@metrics'
)->name('triadev.pe.metrics');
