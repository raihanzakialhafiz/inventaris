<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBidangRequest extends FormRequest
{
    /** Otorisasi ditangani middleware role pada route. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code'         => 'required|string|max:20|unique:departments,code',
            'name'         => 'required|string|max:100',
            'head_user_id' => 'nullable|exists:users,id',
            'description'  => 'nullable|string|max:255',
        ];
    }
}
