<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    // Didaftarkan manual (paket dipasang dengan --no-scripts, di luar auto-discovery)
    Barryvdh\DomPDF\ServiceProvider::class,
    Maatwebsite\Excel\ExcelServiceProvider::class,
];
