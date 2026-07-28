<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KategoriRequest extends FormRequest
{
    /** Otorisasi ditangani middleware role pada route. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // ignore(null) saat store, ignore(model) saat update — satu aturan untuk keduanya.
            'name'        => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($this->route('kategori'))],
            'description' => 'nullable|string|max:255',
        ];
    }
}
