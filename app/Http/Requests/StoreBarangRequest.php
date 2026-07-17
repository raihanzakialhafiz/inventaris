<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreBarangRequest extends FormRequest
{
    /** Otorisasi ditangani middleware role pada route. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code'          => 'required|string|max:30|unique:items,code',
            'category_id'   => 'required|exists:categories,id',
            // Nama juga unik: dua nama sama berkode beda membingungkan
            // pemilihan barang di permintaan/distribusi.
            'name'          => 'required|string|max:150|unique:items,name',
            'unit'          => 'required|string|max:30',
            'stock'         => 'required|integer|min:0|max:1000000',
            'minimum_stock' => 'required|integer|min:0|max:100000',
            'reorder_point' => 'nullable|integer|min:0|max:100000',
            'location'      => 'nullable|string|max:100',
            'description'   => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique'          => 'Kode ini sudah dipakai barang lain.',
            'name.unique'          => 'Nama barang ini sudah terdaftar.',
            'category_id.required' => 'Kategori wajib dipilih.',
            'unit.required'        => 'Satuan wajib dipilih.',
        ];
    }

    /**
     * Error form ini tampil inline di dalam modal (bukan toast) — tandai agar
     * partials/flash tidak ikut menampilkannya sebagai toast ganda.
     */
    protected function failedValidation(Validator $validator)
    {
        session()->flash('inline_errors', true);

        parent::failedValidation($validator);
    }
}
