<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePenggunaRequest extends FormRequest
{
    /** Otorisasi ditangani middleware role pada route. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => 'required|string|max:150',
            'email'         => 'required|email|unique:users,email',
            // Sengaja tidak dipaksa 18 digit: format NIP berbeda antar status
            // kepegawaian, dan tidak semua pengguna sistem punya NIP.
            'nip'           => 'nullable|string|max:30',
            'jabatan'       => 'nullable|string|max:150',
            // Kebijakan password seragam (sama dengan ganti password di Profil).
            'password'      => ['required', 'string', 'min:8', 'regex:/[a-zA-Z]/', 'regex:/[0-9]/'],
            'role'          => 'required|in:admin,kasubag_umum,petugas_gudang,kepala_bidang,pimpinan',
            'department_id' => 'nullable|required_if:role,kepala_bidang|exists:departments,id',
            'is_active'     => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'password.min'             => 'Password minimal 8 karakter.',
            'password.regex'           => 'Password harus kombinasi huruf dan angka.',
            'department_id.required_if' => 'Bidang wajib dipilih untuk Kepala Bidang.',
        ];
    }
}
