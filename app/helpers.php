<?php

use App\Models\Setting;

if (! function_exists('setting')) {
    /**
     * Ambil nilai Pengaturan Sistem (dinamis, di-cache).
     * Contoh: setting('app_name', 'SIIB')
     */
    function setting(string $key, $default = null)
    {
        return Setting::get($key, $default);
    }
}

if (! function_exists('like_contains')) {
    /**
     * Pola LIKE "mengandung kata" yang aman: wildcard (% _ \) pada input
     * pengguna di-escape agar dicari sebagai teks biasa, bukan pola.
     * Contoh: ->where('name', 'like', like_contains($s))
     */
    function like_contains(string $term): string
    {
        return '%' . addcslashes($term, '\\%_') . '%';
    }
}
