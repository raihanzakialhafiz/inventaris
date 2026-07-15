<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockOpnameRequest extends FormRequest
{
    /** Otorisasi ditangani middleware role pada route. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'note'     => 'nullable|string|max:500',
            'counts'   => 'required|array|min:1',      // [item_id => jumlah fisik]
            'counts.*' => 'required|integer|min:0|max:1000000',
        ];
    }

    public function messages(): array
    {
        return [
            'counts.required' => 'Tidak ada barang untuk diperiksa.',
            'counts.*.min'    => 'Jumlah fisik tidak boleh negatif.',
        ];
    }
}
