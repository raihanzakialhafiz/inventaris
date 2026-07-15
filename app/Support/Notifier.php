<?php

namespace App\Support;

use App\Mail\ActionNotificationMail;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * Titik tunggal pengiriman notifikasi: membuat notifikasi in-app DAN
 * mengirim email. Kegagalan email di-report saja, TIDAK menggagalkan aksi.
 *
 * Panggil SETELAH transaksi DB commit (email SMTP lambat — jangan menahan lock).
 */
class Notifier
{
    public static function toUser(
        User $user,
        string $type,
        string $message,
        ?int $referenceId = null,
        ?string $referenceType = null,
        ?string $url = null,
        ?string $urlLabel = null,
    ): void {
        Notification::create([
            'user_id'        => $user->id,
            'type'           => $type,
            'message'        => $message,
            'reference_id'   => $referenceId,
            'reference_type' => $referenceType,
            'is_read'        => false,
        ]);

        self::email($user, $type, $message, $url, $urlLabel);
    }

    /** @param iterable<User> $users */
    public static function toUsers(
        iterable $users,
        string $type,
        string $message,
        ?int $referenceId = null,
        ?string $referenceType = null,
        ?string $url = null,
        ?string $urlLabel = null,
    ): void {
        foreach ($users as $user) {
            self::toUser($user, $type, $message, $referenceId, $referenceType, $url, $urlLabel);
        }
    }

    private static function email(User $user, string $type, string $message, ?string $url, ?string $urlLabel): void
    {
        if (empty($user->email)) {
            return;
        }

        $kirim = function () use ($user, $type, $message, $url, $urlLabel): void {
            try {
                Mail::to($user->email)->queue(
                    new ActionNotificationMail($user->name, self::subject($type), $message, $url, $urlLabel)
                );
            } catch (\Throwable $e) {
                // Email gagal tidak boleh menggagalkan aksi utama.
                report($e);
            }
        };

        // Pada request HTTP dengan queue sync, SMTP yang lambat ikut menahan
        // respons — tunda ke fase terminating (setelah respons terkirim).
        // Di console (scheduler/command) tidak ada pengguna menunggu: kirim langsung.
        app()->runningInConsole() ? $kirim() : app()->terminating($kirim);
    }

    private static function subject(string $type): string
    {
        return match ($type) {
            'new_request'         => 'Permintaan Barang Baru',
            'request_approved'    => 'Permintaan Anda Disetujui',
            'request_rejected'    => 'Permintaan Anda Ditolak',
            'ready_to_distribute' => 'Permintaan Siap Didistribusikan',
            'request_distributed' => 'Permintaan Anda Telah Didistribusikan',
            'low_stock'           => 'Peringatan: Stok Menipis',
            'reorder'             => 'Barang Perlu Dipesan Ulang',
            default               => 'Pemberitahuan Sistem',
        };
    }
}
