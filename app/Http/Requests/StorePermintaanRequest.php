<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi dasar pengajuan permintaan. Aturan kondisional (justifikasi wajib
 * saat over-request) tetap di controller karena bergantung data kuota barang.
 */
class StorePermintaanRequest extends FormRequest
{
    /** Otorisasi ditangani middleware role pada route. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'note'            => 'nullable|string|max:1000',
            'justification'   => 'nullable|string|max:1000',
            'items'           => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            // Batas atas mencegah angka absurd merusak data stok.
            'items.*.qty'     => 'required|integer|min:1|max:100000',
        ];
    }
}
