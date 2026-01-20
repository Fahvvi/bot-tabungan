<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;
use App\Models\Wallet;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            return view('welcome');
        }

        $user = Auth::user();
        
        // Data untuk Dashboard
        $wallets = $user->wallets; // Data dompet untuk dropdown
        $totalBalance = $user->wallets()->sum('balance');
        
        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        $income = Transaction::where('user_id', $user->id)
            ->where('type', 'income')
            ->whereBetween('transaction_date', [$start, $end])
            ->sum('amount');

        $expense = Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$start, $end])
            ->sum('amount');

        $recentTransactions = Transaction::where('user_id', $user->id)
            ->with('wallet')
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->take(10) // Tampilkan 10 terakhir
            ->get();

        return view('welcome', compact('user', 'wallets', 'totalBalance', 'income', 'expense', 'recentTransactions'));
    }

    public function storeTransaction(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'type' => 'required|in:income,expense',
            'wallet_id' => 'required|exists:wallets,id',
            'description' => 'required|string|max:255',
            'date' => 'required|date',
        ]);

        $user = Auth::user();
        $wallet = Wallet::where('id', $request->wallet_id)->where('user_id', $user->id)->firstOrFail();

        try {
            DB::transaction(function () use ($request, $user, $wallet) {
                // 1. Buat Transaksi
                Transaction::create([
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                    'type' => $request->type,
                    'amount' => $request->amount,
                    'description' => $request->description,
                    'transaction_date' => $request->date,
                ]);

                // 2. Update Saldo Dompet
                if ($request->type === 'income') {
                    $wallet->increment('balance', $request->amount);
                } else {
                    $wallet->decrement('balance', $request->amount);
                }
            });

            return redirect()->route('home')->with('success', 'Transaksi berhasil disimpan!');
        } catch (\Exception $e) {
            return redirect()->route('home')->with('error', 'Gagal menyimpan transaksi: ' . $e->getMessage());
        }
    }
}