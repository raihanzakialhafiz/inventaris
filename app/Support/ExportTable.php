<?php

namespace App\Support;

/**
 * Kerangka data tabular untuk ekspor PDF/Excel — dipakai bersama oleh
 * Laporan, Audit Log, Barang Masuk, dan Stock Opname (template
 * laporan.pdf / laporan.excel + LaporanExport).
 */
class ExportTable
{
    public static function make(
        string $title,
        array $headers,
        array $rows,
        string $filename,
        ?string $period = null,
        ?string $deptName = null,
    ): array {
        return [
            'type'        => 'umum',
            'title'       => $title,
            'headers'     => $headers,
            'rows'        => $rows,
            'period'      => $period,
            'deptName'    => $deptName,
            'filename'    => $filename,
            'institution' => setting('institution_name', 'Instansi'),
            'appName'     => setting('app_name', 'SIIB'),
            'address'     => setting('address'),
            'logo'        => setting('logo') ? public_path('storage/' . setting('logo')) : null,
        ];
    }
}
