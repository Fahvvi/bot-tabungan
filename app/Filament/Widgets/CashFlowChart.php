<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class CashFlowChart extends ChartWidget
{
    protected static ?string $heading = 'Arus Kas Bulan Ini (Harian)';
    protected int | string | array $columnSpan = 'full'; // Agar grafik lebar memenuhi layar
    protected static ?int $sort = 2; // Urutan ke-2 setelah Stats

    protected function getData(): array
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

        // 1. Ambil Data Pemasukan Harian
        $incomeData = Trend::query(Transaction::where('type', 'income'))
            ->between(start: $start, end: $end)
            ->perDay()
            ->sum('amount');

        // 2. Ambil Data Pengeluaran Harian
        $expenseData = Trend::query(Transaction::where('type', 'expense'))
            ->between(start: $start, end: $end)
            ->perDay()
            ->sum('amount');

        return [
            'datasets' => [
                [
                    'label' => 'Pemasukan',
                    'data' => $incomeData->map(fn (TrendValue $value) => $value->aggregate),
                    'borderColor' => '#10b981', // Hijau Emerald
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                ],
                [
                    'label' => 'Pengeluaran',
                    'data' => $expenseData->map(fn (TrendValue $value) => $value->aggregate),
                    'borderColor' => '#ef4444', // Merah Red
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $incomeData->map(fn (TrendValue $value) => $value->date),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}