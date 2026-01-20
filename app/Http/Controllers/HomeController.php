<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Transaction;
use App\Models\Wallet;
use Carbon\Carbon;

class HomeController extends Controller
{
    public function index()
    {
        // 1. Jika User Belum Login -> Tampilkan Halaman Login Style Grass
        if (!Auth::check()) {
            return view('welcome');
        }

        $user = Auth::user();
        
        // 2. Hitung Data (Menggunakan Relasi yang Benar)
        // - Relasi wallets()
        $totalBalance = $user->wallets()->sum('balance'); 
        
        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        // - Menggunakan transaction_date
        $income = Transaction::where('user_id', $user->id)
            ->where('type', 'income')
            ->whereBetween('transaction_date', [$start, $end])
            ->sum('amount');

        $expense = Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$start, $end])
            ->sum('amount');

        // - Eager load 'wallet' untuk efisiensi
        $recentTransactions = Transaction::where('user_id', $user->id)
            ->with('wallet') 
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->take(6) // Ambil 6 agar pas di layout grid
            ->get();

        return view('welcome', compact('user', 'totalBalance', 'income', 'expense', 'recentTransactions'));
    }
}