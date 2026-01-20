<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\Goal; // Pastikan Model Goal diimport
use Carbon\Carbon;

/*
|--------------------------------------------------------------------------
| Web Routes (Dashboard + Goals Logic)
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    // 1. Jika Belum Login -> Tampilkan Landing Page
    if (!Auth::check()) {
        return view('welcome');
    }

    // 2. Jika Sudah Login -> Siapkan Data
    $user = Auth::user();
    $wallets = $user->wallets; // Untuk dropdown
    
    // Hitung Total Saldo
    $totalBalance = $user->wallets()->sum('balance'); 
    
    // Data Transaksi Bulan Ini
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

    // Ambil Data Goals (Termasuk Tabungan Bersama)
    // Relasi 'users' digunakan untuk menghitung jumlah member (joint account)
    $goals = $user->goals()->with('users')->get()->map(function ($goal) {
        // Hitung persentase progress
        $goal->progress = $goal->target_amount > 0 
            ? min(100, round(($goal->current_amount / $goal->target_amount) * 100)) 
            : 0;
        
        // Cek apakah ini tabungan bersama? (Member > 1)
        $goal->is_joint = $goal->users->count() > 1;
        
        return $goal;
    });

    // 10 Transaksi Terakhir
    $recentTransactions = Transaction::where('user_id', $user->id)
        ->with('wallet') 
        ->orderBy('transaction_date', 'desc')
        ->orderBy('created_at', 'desc')
        ->take(10)
        ->get();

    // Kirim semua variable ke View
    return view('welcome', compact('user', 'wallets', 'totalBalance', 'income', 'expense', 'recentTransactions', 'goals'));

})->name('home');

// === ROUTE UNTUK SIMPAN TRANSAKSI ===
Route::post('/transaction', function (Request $request) {
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
            Transaction::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'type' => $request->type,
                'amount' => $request->amount,
                'description' => $request->description,
                'transaction_date' => $request->date,
            ]);

            if ($request->type === 'income') {
                $wallet->increment('balance', $request->amount);
            } else {
                $wallet->decrement('balance', $request->amount);
            }
        });

        return redirect()->route('home')->with('success', 'Transaksi berhasil disimpan!');
    } catch (\Exception $e) {
        return redirect()->route('home')->with('error', 'Gagal: ' . $e->getMessage());
    }
})->name('transaction.store')->middleware('auth');

// === ROUTE LOGOUT ===
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->name('logout');

// === ROUTE RESET PASSWORD (WAJIB) ===
Route::get('/reset-password/{token}', function ($token, Request $request) {
    return redirect()->route('filament.admin.auth.password-reset.reset', [
        'token' => $token,
        'email' => $request->email,
    ]);
})->name('password.reset');