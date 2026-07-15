<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotifikasiController extends Controller
{
    /** Jumlah belum terbaca — dipanggil berkala oleh app-shell.js (badge live). */
    public function count()
    {
        return response()->json([
            'unread' => Notification::where('user_id', auth()->id())->where('is_read', false)->count(),
        ]);
    }

    /** Pusat notifikasi: daftar seluruh notifikasi milik pengguna. */
    public function index()
    {
        $notifications = Notification::where('user_id', auth()->id())
            ->latest()
            ->paginate(20);

        return view('notifikasi.index', compact('notifications'));
    }

    /** Tandai satu notifikasi terbaca lalu arahkan ke sumber terkait. */
    public function read(Request $request, Notification $notifikasi)
    {
        abort_unless($notifikasi->user_id === auth()->id(), 403);

        // Prefetch/prerender browser bukan klik pengguna — jangan tandai terbaca.
        $purpose  = $request->headers->get('Sec-Purpose') ?? $request->headers->get('Purpose', '');
        $prefetch = str_contains($purpose, 'prefetch') || str_contains($purpose, 'prerender');

        if (! $prefetch && ! $notifikasi->is_read) {
            $notifikasi->update(['is_read' => true, 'read_at' => now()]);
        }

        return redirect($this->targetUrl($notifikasi));
    }

    /** Tandai semua notifikasi milik pengguna sebagai terbaca. */
    public function readAll()
    {
        Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return back()->with('success', 'Semua notifikasi ditandai telah dibaca.');
    }

    /** Hapus satu notifikasi milik pengguna. */
    public function destroy(Notification $notifikasi)
    {
        abort_unless($notifikasi->user_id === auth()->id(), 403);
        $notifikasi->delete();

        return back()->with('success', 'Notifikasi dihapus.');
    }

    /** Hapus seluruh notifikasi milik pengguna. */
    public function destroyAll()
    {
        Notification::where('user_id', auth()->id())->delete();

        return back()->with('success', 'Semua notifikasi dihapus.');
    }

    /** Pemetaan reference_type → URL tujuan saat notifikasi diklik. */
    private function targetUrl(Notification $n): string
    {
        return match ($n->reference_type) {
            'requests' => route('permintaan.show', $n->reference_id),
            'items'    => route('barang.index'),
            default    => route('dashboard'),
        };
    }
}
