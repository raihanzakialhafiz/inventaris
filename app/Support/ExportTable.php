<?php

namespace App\Support;

use App\Models\User;

/**
 * Kerangka data tabular untuk ekspor PDF/Excel — dipakai bersama oleh
 * Laporan, Audit Log, Barang Masuk, dan Stock Opname (template
 * laporan.pdf / laporan.excel + LaporanExport).
 */
class ExportTable
{
    public static function make(
        string $title,
        array $headers,
        array $rows,
        string $filename,
        ?string $period = null,
        ?string $deptName = null,
        bool $withSignerSignature = false,
    ): array {
        return [
            'type'        => 'umum',
            'title'       => $title,
            'headers'     => $headers,
            'rows'        => $rows,
            'period'      => $period,
            'deptName'    => $deptName,
            'filename'    => $filename,
            // Kop surat: baris induk pemerintahan, nama instansi, lalu alamat &
            // email. Alamat sengaja satu teks bebas — tiap instansi menulis
            // telepon/fax dengan format berbeda.
            'government'  => setting('government_name'),
            'institution' => setting('institution_name', 'Instansi'),
            'appName'     => setting('app_name', 'SIIB'),
            'address'     => setting('address'),
            'email'       => setting('contact_email'),
            'logo'        => setting('logo') ? public_path('storage/' . setting('logo')) : null,
            // Kota tempat dokumen ditandatangani ("Padang, 16 Juli 2026").
            'place'       => setting('signature_place'),
            'exporter'    => self::signatory(auth()->user()),
            'signer'      => $withSignerSignature ? self::signatory(self::configuredSigner()) : null,
        ];
    }

    /**
     * Pejabat penanda tangan dari Pengaturan. Null bila belum diatur, akunnya
     * sudah dihapus, atau dinonaktifkan — dokumen tetap terbit dengan satu
     * tanda tangan saja, bukan gagal atau mencetak nama pejabat nonaktif.
     */
    private static function configuredSigner(): ?User
    {
        $id = setting('signer_user_id');

        return filled($id)
            ? User::where('id', $id)->where('is_active', true)->first()
            : null;
    }

    /** Data satu blok tanda tangan; null bila penandatangannya tidak ada. */
    private static function signatory(?User $user): ?array
    {
        if (! $user) {
            return null;
        }

        return [
            'name'    => $user->name,
            'nip'     => $user->nip,
            'jabatan' => $user->jabatanLabel(),
        ];
    }
}
