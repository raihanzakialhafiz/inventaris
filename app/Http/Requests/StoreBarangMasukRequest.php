<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBarangMasukRequest extends FormRequest
{
    /** Otorisasi ditangani middleware role pada route. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id'     => 'nullable|exists:suppliers,id',
            'date'            => 'required|date',
            'note'            => 'nullable|string|max:500',
            'items'           => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            // Batas atas mencegah angka absurd merusak data stok.
            'items.*.qty'     => 'required|integer|min:1|max:100000',
        ];
    }
}
