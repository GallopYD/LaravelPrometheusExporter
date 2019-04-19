<?php

Route::get(
    'juhu/pe/metrics',
    \GallopYD\PrometheusExporter\Controller\PrometheusExporterController::class . '@metrics'
)->name('juhu.pe.metrics');
