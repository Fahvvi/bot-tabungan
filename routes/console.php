<?php

use Illuminate\Support\Facades\Schedule;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

// Jadwal Hapus Data > 1 Tahun (Jalan setiap hari jam 00:00)
Schedule::call(function () {
    // 1. Hitung tanggal batas (Hari ini dikurangi 1 tahun)
    $cutoffDate = now()->subYear();

    // 2. Hapus transaksi yang dibuat sebelum tanggal batas
    $deleted = Transaction::where('created_at', '<', $cutoffDate)->delete();

    if ($deleted > 0) {
        Log::info("Auto-Prune: Berhasil menghapus {$deleted} transaksi lama (> 1 tahun).");
    }
    
})->daily();