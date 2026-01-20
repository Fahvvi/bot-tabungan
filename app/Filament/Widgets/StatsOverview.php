<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use App\Models\Wallet;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        // Hitung Data Bulan Ini
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $totalSaldo = Wallet::sum('balance');
        
        $pemasukan = Transaction::where('type', 'income')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        $pengeluaran = Transaction::where('type', 'expense')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        return [
            Stat::make('Total Saldo (Semua Dompet)', 'Rp ' . number_format($totalSaldo, 0, ',', '.'))
                ->description('Aset Lancar')
                ->descriptionIcon('heroicon-m-wallet')
                ->color('primary'), // Biru

            Stat::make('Pemasukan (Bulan Ini)', 'Rp ' . number_format($pemasukan, 0, ',', '.'))
                ->description('Income')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'), // Hijau

            Stat::make('Pengeluaran (Bulan Ini)', 'Rp ' . number_format($pengeluaran, 0, ',', '.'))
                ->description('Expense')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'), // Merah
        ];
    }
}