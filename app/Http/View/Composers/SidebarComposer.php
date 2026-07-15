<?php

namespace App\Http\View\Composers;

use App\Models\Item;
use App\Models\Notification;
use App\Models\Request as ItemRequest;
use Illuminate\View\View;

class SidebarComposer
{
    public function compose(View $view): void
    {
        $user = auth()->user();
        if (! $user) return;

        $lowStockCount = Item::whereColumn('stock', '<=', 'minimum_stock')->count();

        $queueCount = in_array($user->role, ['admin', 'petugas_gudang'])
            ? ItemRequest::where('status', 'disetujui')->count()
            : 0;

        $pendingCount = in_array($user->role, ['admin', 'kasubag_umum'])
            ? ItemRequest::where('status', 'pending')->count()
            : 0;

        $unreadNotifCount = Notification::where('user_id', $user->id)
            ->where('is_read', false)->count();

        // Dropdown navbar hanya menampilkan notifikasi yang BELUM dibaca —
        // begitu dibuka/dibaca, hilang dari daftar ini (riwayat penuh tetap
        // tersedia di halaman /notifikasi).
        $recentNotifs = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->latest()->limit(6)->get();

        $view->with(compact(
            'lowStockCount', 'queueCount', 'pendingCount',
            'unreadNotifCount', 'recentNotifs'
        ));
    }
}
