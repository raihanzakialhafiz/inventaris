<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePengaturanRequest extends FormRequest
{
    /** Otorisasi ditangani middleware role pada route. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'app_name'         => 'required|string|max:100',
            // Baris induk pada kop, mis. "PEMERINTAH PROVINSI SUMATERA BARAT".
            'government_name'  => 'nullable|string|max:150',
            'institution_name' => 'nullable|string|max:150',
            // Memuat alamat + telepon + fax + kota dalam satu baris utuh.
            'address'          => 'nullable|string|max:255',
            'footer_text'      => 'nullable|string|max:255',
            'contact_email'    => 'nullable|email|max:100',
            'session_timeout'  => 'required|integer|min:1|max:1440',
            // Pejabat penanda tangan laporan. Sengaja merujuk pengguna (bukan nama
            // bebas) agar nama/NIP/jabatan ikut berubah saat data pengguna diperbarui.
            'signer_user_id'   => 'nullable|exists:users,id',
            // Kota tempat penandatanganan, mendahului tanggal pada blok tanda tangan.
            'signature_place'  => 'nullable|string|max:100',
            // SVG sengaja tidak diizinkan — dapat memuat skrip (stored XSS).
            'logo'             => 'nullable|image|mimes:png,jpg,jpeg,webp|max:1024',
            // Rule `image` tidak mengenal .ico → pakai `file|mimes` agar ico valid.
            'favicon'          => 'nullable|file|mimes:png,ico|max:256',
            'login_image'      => 'nullable|image|mimes:png,jpg,jpeg,webp|max:3072',
        ];
    }
}
