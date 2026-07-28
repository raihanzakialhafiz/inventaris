<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BidangRequest extends FormRequest
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
            'code'         => ['required', 'string', 'max:20', Rule::unique('departments', 'code')->ignore($this->route('bidang'))],
            'name'         => 'required|string|max:100',
            'head_user_id' => 'nullable|exists:users,id',
            'description'  => 'nullable|string|max:255',
        ];
    }
}
