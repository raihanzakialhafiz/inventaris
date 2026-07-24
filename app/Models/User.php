<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use Auditable, HasFactory, Notifiable;

    /**
     * password: hash tidak boleh bocor ke log (ganti password dicatat manual
     * sebagai 'change_password'); counter login & lockout hanyalah noise
     * yang berubah di tiap percobaan login.
     */
    protected $auditExclude = ['password', 'failed_login_count', 'locked_until', 'email_verified_at'];

    protected $fillable = [
        'name',
        'email',
        'nip',
        'jabatan',
        'password',
        'role',
        'department_id',
        'is_active',
        'failed_login_count',
        'locked_until',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at'  => 'datetime',
            'password'           => 'hashed',
            'is_active'          => 'boolean',
            'locked_until'       => 'datetime',
        ];
    }

    public function department(): BelongsTo
    {
        // withTrashed: profil/daftar pengguna tetap tampil walau bidang di-soft-delete.
        return $this->belongsTo(Department::class)->withTrashed();
    }

    public function requests(): HasMany
    {
        return $this->hasMany(Request::class);
    }

    public function approvedRequests(): HasMany
    {
        return $this->hasMany(Request::class, 'approver_id');
    }

    public function isAdmin(): bool        { return $this->role === 'admin'; }
    public function isKepalaBidang(): bool { return $this->role === 'kepala_bidang'; }
    public function isKasubagUmum(): bool  { return $this->role === 'kasubag_umum'; }
    public function isPetugasGudang(): bool{ return $this->role === 'petugas_gudang'; }
    public function isPimpinan(): bool     { return $this->role === 'pimpinan'; }
    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    public function initials(): string
    {
        return collect(explode(' ', $this->name))
            ->take(2)
            ->map(fn($w) => strtoupper($w[0]))
            ->implode('');
    }

    public function roleLabel(): string
    {
        return match($this->role) {
            'admin'          => 'Administrator',
            'kasubag_umum'   => 'Kasubag Umum',
            'petugas_gudang' => 'Petugas Gudang',
            'kepala_bidang'  => 'Kepala Bidang',
            'pimpinan'       => 'Pimpinan',
            default          => $this->role,
        };
    }

    /**
     * Jabatan untuk blok tanda tangan laporan. Jatuh ke label peran bila belum
     * diisi agar dokumen tetap terbit — peran sistem memang bukan jabatan resmi,
     * tapi itu lebih baik daripada baris jabatan yang kosong di bawah nama.
     */
    public function jabatanLabel(): string
    {
        return filled($this->jabatan) ? $this->jabatan : $this->roleLabel();
    }

    /**
     * Warna avatar per peran. Sumber tunggal — halaman login memanggil ini juga
     * lewat colorForRole(), jadi daftarnya tidak pernah bercabang dua.
     *
     * Semua nilai dipilih agar inisial PUTIH di atasnya lolos WCAG AA (4.5:1).
     * Set sebelumnya gagal di tiga dari lima peran: kepala_bidang #EA580C
     * (3.6:1), pimpinan #0891B2 (3.7:1), dan kasubag_umum #0D9488 (3.8:1) —
     * inisialnya dirender 11–14px tebal, yang belum termasuk "teks besar".
     */
    public static function colorForRole(?string $role): string
    {
        return match($role) {
            'admin'          => '#6D28D9',  // 7.1:1
            'kasubag_umum'   => '#0F766E',  // 5.5:1
            'petugas_gudang' => '#1D4ED8',  // 6.7:1
            'kepala_bidang'  => '#C2410C',  // 5.2:1
            'pimpinan'       => '#0E7490',  // 5.4:1
            default          => '#475569',  // 7.6:1
        };
    }

    public function roleColor(): string
    {
        return static::colorForRole($this->role);
    }
}
