<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class LaporanExport implements FromView
{
    public function __construct(private array $table) {}

    public function view(): View
    {
        return view('laporan.excel', $this->table);
    }
}
