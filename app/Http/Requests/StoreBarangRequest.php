<?php

namespace App\Http\Requests;

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
            'name'          => 'required|string|max:150',
            'unit'          => 'required|string|max:30',
            'stock'         => 'required|integer|min:0|max:1000000',
            'minimum_stock' => 'required|integer|min:0|max:100000',
            'reorder_point' => 'nullable|integer|min:0|max:100000',
            'location'      => 'nullable|string|max:100',
            'description'   => 'nullable|string',
        ];
    }
}
