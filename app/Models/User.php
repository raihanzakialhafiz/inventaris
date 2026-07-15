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

    public function roleColor(): string
    {
        return match($this->role) {
            'admin'          => '#7C3AED',
            'kasubag_umum'   => '#0D9488',
            'petugas_gudang' => '#2563EB',
            'kepala_bidang'  => '#EA580C',
            'pimpinan'       => '#0891B2',
            default          => '#64748B',
        };
    }
}
