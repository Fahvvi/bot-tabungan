<?php

namespace App\Exports;

use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Carbon\Carbon;

class TransactionExport implements FromCollection, WithHeadings, WithMapping
{
    protected $userId;
    protected $startDate;
    protected $endDate;

    public function __construct($userId, $startDate, $endDate)
    {
        $this->userId = $userId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        return Transaction::where('user_id', $this->userId)
            ->whereBetween('transaction_date', [$this->startDate, $this->endDate])
            ->with('wallet')
            ->orderBy('transaction_date')
            ->get();
    }

    public function headings(): array
    {
        return ['Tanggal', 'Tipe', 'Dompet', 'Keterangan', 'Jumlah'];
    }

    public function map($transaction): array
    {
        return [
            Carbon::parse($transaction->transaction_date)->format('d-m-Y'),
            strtoupper($transaction->type),
            $transaction->wallet->name ?? 'Hapus',
            $transaction->description,
            $transaction->amount
        ];
    }
}