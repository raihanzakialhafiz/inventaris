<?php

namespace App\Http\Requests;

use App\Models\RequestQuota;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreKuotaRequest extends FormRequest
{
    /** Otorisasi ditangani middleware role pada route. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Disembunyikan dari modal & dikunci di sini — merge() menimpa apa pun yang
     * dikirim browser. Kuota = batas nyata (threshold 100%), cooldown mati,
     * selalu bulanan, berlaku hari ini. Kolomnya tetap ada: baris lama dengan
     * nilai lain tetap dihormati, dan field bisa dimunculkan lagi kapan saja.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'period_type'       => 'monthly',
            'threshold_percent' => 100,
            'cooldown_days'     => 0,
            'effective_from'    => today()->toDateString(),
        ]);
    }

    public function rules(): array
    {
        return [
            // null = berlaku untuk SEMUA bidang / barang / kategori.
            'department_id'     => 'nullable|exists:departments,id',
            'item_id'           => 'nullable|exists:items,id',
            'category_id'       => 'nullable|exists:categories,id',
            'period_type'       => 'required|in:monthly,quarterly,yearly',
            'quota_quantity'    => 'required|integer|min:1',
            'threshold_percent' => 'required|integer|min:100|max:500',
            'cooldown_days'     => 'required|integer|min:0',
            'policy'            => 'required|in:warn,block',
            'effective_from'    => 'required|date',
        ];
    }

    /**
     * Satu kombinasi (bidang, barang, kategori) hanya boleh punya satu aturan
     * aktif — bila ganda, aturan mana yang berlaku menjadi tidak menentu.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $duplikat = RequestQuota::where('department_id', $this->input('department_id') ?: null)
                ->where('item_id', $this->input('item_id') ?: null)
                ->where('category_id', $this->input('category_id') ?: null)
                ->where('is_active', true)
                ->exists();

            if ($duplikat) {
                $v->errors()->add('department_id', 'Aturan kuota aktif untuk kombinasi bidang/barang/kategori ini sudah ada. Ubah aturan tersebut atau nonaktifkan dulu.');
            }
        });
    }
}
