<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SatuanRequest extends FormRequest
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
            'name'        => ['required', 'string', 'max:30', Rule::unique('units', 'name')->ignore($this->route('satuan'))],
            'description' => 'nullable|string|max:100',
        ];
    }
}
