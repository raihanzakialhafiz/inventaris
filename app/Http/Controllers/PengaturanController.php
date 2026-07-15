<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePengaturanRequest;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PengaturanController extends Controller
{
    /** Kunci teks yang boleh diubah beserta tipenya. */
    private const TEXT_KEYS = [
        'app_name'         => 'text',
        'institution_name' => 'text',
        'address'          => 'text',
        'footer_text'      => 'text',
        'contact_email'    => 'email',
        'contact_phone'    => 'phone',
        'session_timeout'  => 'number',
    ];

    public function index()
    {
        $settings = Setting::pluck('value', 'key');

        return view('pengaturan.index', compact('settings'));
    }

    public function update(UpdatePengaturanRequest $request)
    {
        foreach (self::TEXT_KEYS as $key => $type) {
            Setting::updateOrCreate(['key' => $key], ['value' => $request->input($key), 'type' => $type]);
        }

        $this->handleUpload($request, 'logo');
        $this->handleUpload($request, 'favicon');
        $this->handleUpload($request, 'login_image');

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }

    /** Hapus salah satu gambar branding (logo / favicon / login_image). */
    public function deleteImage(string $key)
    {
        abort_unless(in_array($key, ['logo', 'favicon', 'login_image'], true), 404);

        $old = Setting::where('key', $key)->value('value');
        if ($old && Storage::disk('public')->exists($old)) {
            Storage::disk('public')->delete($old);
        }
        Setting::updateOrCreate(['key' => $key], ['value' => null, 'type' => 'image']);

        return back()->with('success', 'Gambar berhasil dihapus.');
    }

    /** Simpan berkas gambar ke storage publik lalu simpan path-nya. */
    private function handleUpload(Request $request, string $key): void
    {
        if (! $request->hasFile($key)) {
            return;
        }

        // Hapus berkas lama bila ada.
        $old = Setting::where('key', $key)->value('value');
        if ($old && Storage::disk('public')->exists($old)) {
            Storage::disk('public')->delete($old);
        }

        $path = $request->file($key)->store('branding', 'public');
        Setting::updateOrCreate(['key' => $key], ['value' => $path, 'type' => 'image']);
    }
}
