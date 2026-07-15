<?php

namespace App\Http\Requests;

use App\Models\RequestQuota;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateKuotaRequest extends FormRequest
{
    /** Otorisasi ditangani middleware role pada route. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quota_quantity'    => 'required|integer|min:1',
            'threshold_percent' => 'required|integer|min:100|max:500',
            'cooldown_days'     => 'required|integer|min:0',
            'policy'            => 'required|in:warn,block',
            'is_active'         => 'boolean',
        ];
    }

    /**
     * Mengaktifkan kembali aturan tidak boleh menabrak aturan aktif lain
     * dengan cakupan (bidang, barang, kategori) yang sama.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if (! $this->boolean('is_active', true)) {
                return; // menonaktifkan aturan selalu aman
            }

            /** @var RequestQuota $kuotum */
            $kuotum = $this->route('kuotum');

            $duplikat = RequestQuota::whereKeyNot($kuotum->id)
                ->where('department_id', $kuotum->department_id)
                ->where('item_id', $kuotum->item_id)
                ->where('category_id', $kuotum->category_id)
                ->where('is_active', true)
                ->exists();

            if ($duplikat) {
                $v->errors()->add('is_active', 'Sudah ada aturan aktif lain dengan cakupan bidang/barang/kategori yang sama. Nonaktifkan aturan tersebut lebih dulu.');
            }
        });
    }
}
