<?php

/** @var SergiX44\Nutgram\Nutgram $bot */

use App\Models\User;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\Goal; 
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/*
|--------------------------------------------------------------------------
| 0. LOG & PING
|--------------------------------------------------------------------------
*/
$bot->middleware(function (Nutgram $bot, $next) {
    echo "[LOG] " . $bot->message()->text . "\n";
    $next($bot);
});

$bot->onCommand('ping', function (Nutgram $bot) {
    $bot->sendMessage("Pong! 🏓");
});

/*
|--------------------------------------------------------------------------
| 1. MENU UTAMA
|--------------------------------------------------------------------------
*/
$bot->onCommand('start', function (Nutgram $bot) {
    $chatId = $bot->chatId();
    $user = User::where('telegram_chat_id', $chatId)->first();

    if ($user) {
        $msg = "👋 *Halo, {$user->name}!*\n\n";
        
        $msg .= "💰 *Arus Kas*\n";
        $msg .= "• `/masuk 10jt Gaji` (Pemasukan)\n";
        $msg .= "• `25k Nasi Padang` (Pengeluaran)\n";
        $msg .= "• `/edit` (Koreksi transaksi)\n\n";

        $msg .= "🎯 *Goals*\n";
        $msg .= "• `/buatgoal 50jt Nikah`\n";
        $msg .= "• `/nabung 500k Nikah`\n";
        $msg .= "• `/goals` (Cek Progress)\n\n";

        $msg .= "📊 *Laporan*\n";
        $msg .= "• `/rekap` (Laporan Keuangan)";

        $bot->sendMessage($msg, $chatId, null, 'Markdown');
    } else {
        $bot->sendMessage("👋 *Halo!*\nID Telegram kamu: `{$chatId}`\nCopy ID ini ke Dashboard Web agar terhubung.", $chatId, null, 'Markdown');
    }
});

/*
|--------------------------------------------------------------------------
| 2. FITUR PEMASUKAN
|--------------------------------------------------------------------------
*/
$bot->onText('^/(?:masuk|pemasukan)\s+([0-9.,]+[a-zA-Z]*)\s+(.*)', function (Nutgram $bot, $amtStr, $desc) {
    try {
        $user = User::where('telegram_chat_id', $bot->chatId())->first();
        if (!$user) return $bot->sendMessage("❌ Akun belum terhubung.");

        $cleanStr = str_replace([',', '.'], '', strtolower($amtStr)); 
        $amount = str_ireplace(['k', 'jt', 'juta', 'm'], ['000', '000000', '000000', '000000'], $cleanStr);
        
        if (!is_numeric($amount)) return $bot->sendMessage("❌ Format salah.");

        // Smart Wallet
        $wallets = $user->wallets()->get();
        $selectedWallet = null;
        foreach ($wallets as $w) {
            if (stripos($desc, $w->name) !== false) {
                $selectedWallet = $w;
                break;
            }
        }
        if (!$selectedWallet) {
            if ($wallets->isEmpty()) {
                $selectedWallet = Wallet::create(['user_id' => $user->id, 'name' => 'Tunai (Cash)', 'type' => 'cash', 'balance' => 0]);
            } else {
                $selectedWallet = $wallets->first();
            }
        }

        DB::transaction(function () use ($user, $selectedWallet, $amount, $desc) {
            Transaction::create([
                'user_id' => $user->id,
                'wallet_id' => $selectedWallet->id,
                'type' => 'income',
                'amount' => $amount,
                'description' => trim($desc),
                'transaction_date' => now()
            ]);
            $selectedWallet->increment('balance', $amount);
        });

        $bot->sendMessage(
            "💰 *Pemasukan Diterima!*\n➕ Rp " . number_format($amount) . "\n📝 {$desc}\n💳 Masuk ke: **{$selectedWallet->name}**", 
            $bot->chatId(), null, 'Markdown'
        );

    } catch (\Throwable $e) {
        $bot->sendMessage("⚠️ Error: " . $e->getMessage());
    }
});

/*
|--------------------------------------------------------------------------
| 3. FITUR EDIT TRANSAKSI
|--------------------------------------------------------------------------
*/
$bot->onCommand('edit', function (Nutgram $bot) {
    $user = User::where('telegram_chat_id', $bot->chatId())->first();
    $latest = Transaction::where('user_id', $user->id)->latest()->take(3)->get();

    if ($latest->isEmpty()) return $bot->sendMessage("📭 Belum ada data transaksi.");

    $msg = "✏️ *Pilih Transaksi untuk Diedit:*";
    $keyboard = InlineKeyboardMarkup::make();

    foreach ($latest as $tx) {
        $amount = number_format($tx->amount / 1000) . "k"; 
        $desc = Str::limit($tx->description, 15);
        
        // Ikon berdasarkan tipe
        $icon = match($tx->type) {
            'income' => '➕',
            'transfer_to_goal' => '🐖',
            default => '➖'
        };
        
        $label = "{$icon} {$amount} {$desc}";
        $keyboard->addRow(InlineKeyboardButton::make($label, callback_data: "edit_tx|{$tx->id}"));
    }

    $bot->sendMessage($msg, $bot->chatId(), null, 'Markdown', reply_markup: $keyboard);
});

$bot->onCallbackQueryData('edit_tx|{id}', function (Nutgram $bot, $id) {
    if (empty($id)) { $parts = explode('|', $bot->callbackQuery()->data); $id = $parts[1] ?? null; }
    $tx = Transaction::find($id);
    if (!$tx) return $bot->sendMessage("❌ Data tidak ditemukan.");

    Cache::put('editing_user_' . $bot->chatId(), $id, 300);
    $bot->sendMessage("📝 *Mode Edit Aktif!*\nData Lama: `" . number_format($tx->amount) . " " . $tx->description . "`\n👉 Ketik data BARU sekarang.", $bot->chatId(), null, 'Markdown');
    try { $bot->deleteMessage($bot->chatId(), $bot->message()->message_id); } catch (\Throwable $e) {}
});

$bot->onCommand('batal', function (Nutgram $bot) {
    Cache::forget('editing_user_' . $bot->chatId());
    $bot->sendMessage("✅ Edit dibatalkan.");
});

/*
|--------------------------------------------------------------------------
| 4. FITUR GOALS
|--------------------------------------------------------------------------
*/
$bot->onCommand('buatgoal {target} {name}', function (Nutgram $bot, $targetStr, $name) {
    $user = User::where('telegram_chat_id', $bot->chatId())->first();
    $target = str_ireplace(['k', 'jt', 'juta'], ['000', '000000', '000000'], strtolower($targetStr));
    
    if (!is_numeric($target)) return $bot->sendMessage("❌ Format target salah.");
    $code = strtoupper(Str::random(6));

    try {
        DB::transaction(function () use ($user, $target, $name, $code) {
            $goal = Goal::create([ 
                'owner_id' => $user->id,
                'name' => $name,
                'target_amount' => $target,
                'current_amount' => 0,
                'code' => $code
            ]);
            $goal->users()->attach($user->id);
        });
        $bot->sendMessage("✅ Goal **{$name}** berhasil dibuat!\nKode Invite: `{$code}`", $bot->chatId(), null, 'Markdown');

    } catch (\Throwable $e) { $bot->sendMessage("⚠️ Gagal: " . $e->getMessage()); }
});

$bot->onCommand('gabung {code}', function (Nutgram $bot, $code) {
    $user = User::where('telegram_chat_id', $bot->chatId())->first();
    $goal = Goal::where('code', strtoupper($code))->first();
    if (!$goal) return $bot->sendMessage("❌ Kode tidak ditemukan.");
    if ($goal->users()->where('user_id', $user->id)->exists()) return $bot->sendMessage("⚠️ Sudah bergabung.");
    
    $goal->users()->attach($user->id);
    $bot->sendMessage("✅ Berhasil gabung ke **{$goal->name}**!");
});

$bot->onCommand('goals', function (Nutgram $bot) {
    $user = User::where('telegram_chat_id', $bot->chatId())->first();
    $goals = $user->goals()->get();
    if ($goals->isEmpty()) return $bot->sendMessage("Belum ada Goal.");

    $msg = "🎯 *List Goals:*\n";
    foreach ($goals as $g) {
        $pct = $g->target_amount > 0 ? ($g->current_amount / $g->target_amount) * 100 : 0;
        $bar = str_repeat('🟩', floor($pct/10)) . str_repeat('⬜', 10-floor($pct/10));
        $msg .= "\n📌 *{$g->name}* (`{$g->code}`)\n💰 " . number_format($g->current_amount) . " / " . number_format($g->target_amount) . "\n📊 {$bar} (" . round($pct) . "%)\n";
    }
    $bot->sendMessage($msg, $bot->chatId(), null, 'Markdown');
});

$bot->onText('^/nabung\s+([0-9.,]+[a-zA-Z]*)\s+(.*)', function (Nutgram $bot, $amtStr, $goalName) {
    try {
        $user = User::where('telegram_chat_id', $bot->chatId())->first();
        if (!$user) return $bot->sendMessage("❌ Akun belum terhubung.");

        $cleanStr = str_replace([',', '.'], '', strtolower($amtStr)); 
        $amount = str_ireplace(['k', 'jt', 'juta', 'm'], ['000', '000000', '000000', '000000'], $cleanStr);
        if (!is_numeric($amount)) return $bot->sendMessage("❌ Format nominal salah.");

        $goal = $user->goals()->where('name', 'LIKE', "%{$goalName}%")->first();
        if (!$goal) return $bot->sendMessage("❌ Goal tidak ditemukan.");

        $wallet = $user->wallets()->first();
        if ($wallet->balance < $amount) return $bot->sendMessage("❌ Saldo kurang.");

        DB::transaction(function () use ($user, $wallet, $goal, $amount) {
            Transaction::create([
                'user_id' => $user->id, 'wallet_id' => $wallet->id, 'goal_id' => $goal->id,
                'type' => 'transfer_to_goal', 'amount' => $amount, 'description' => "Nabung {$goal->name}", 'transaction_date' => now()
            ]);
            $wallet->decrement('balance', $amount);
            $goal->increment('current_amount', $amount);
        });
        
        $bot->sendMessage("✅ Nabung Rp ".number_format($amount)." ke **{$goal->name}** sukses!", $bot->chatId(), null, 'Markdown');

    } catch (\Throwable $e) { $bot->sendMessage("⚠️ Error: " . $e->getMessage()); }
});

/*
|--------------------------------------------------------------------------
| 5. REKAP & CASHFLOW (DIPERBAIKI)
|--------------------------------------------------------------------------
*/
/*
|--------------------------------------------------------------------------
| FITUR REKAP (SUDAH DIPERBAIKI: Termasuk Tabungan)
|--------------------------------------------------------------------------
*/
$bot->onCommand('rekap', function (Nutgram $bot) {
    try {
        $user = User::where('telegram_chat_id', $bot->chatId())->first();
        if (!$user) return $bot->sendMessage("❌ Akun belum terhubung.");

        $text = $bot->message()->text;
        $params = trim(str_replace('/rekap', '', $text));

        // 1. Tentukan Periode Tanggal
        if (!empty($params)) {
            $dates = explode(' ', $params);
            if (count($dates) != 2) return $bot->sendMessage("❌ Format: /rekap 01-01-2026 31-01-2026");
            $start = Carbon::createFromFormat('d-m-Y', $dates[0])->startOfDay();
            $end = Carbon::createFromFormat('d-m-Y', $dates[1])->endOfDay();
            $title = "Laporan ({$dates[0]} - {$dates[1]})";
        } else {
            $start = Carbon::now()->startOfMonth();
            $end = Carbon::now()->endOfMonth();
            $title = "Bulan Ini (" . Carbon::now()->translatedFormat('M Y') . ")";
        }

        // 2. Query Data
        // A. Pengeluaran (Diurutkan dari Nominal Terbesar)
        $expenses = Transaction::where('user_id', $user->id)
            ->where('type', 'expense') 
            ->whereBetween('transaction_date', [$start, $end])
            ->orderByDesc('amount') // Urutkan nominal terbesar di atas
            ->get();

        // B. Total Pemasukan
        $incomeTotal = Transaction::where('user_id', $user->id)
            ->where('type', 'income') 
            ->whereBetween('transaction_date', [$start, $end])
            ->sum('amount');

        // C. Total Tabungan
        $savingsTotal = Transaction::where('user_id', $user->id)
            ->where('type', 'transfer_to_goal') 
            ->whereBetween('transaction_date', [$start, $end])
            ->sum('amount');

        // Cek jika data kosong
        $expenseTotal = $expenses->sum('amount');
        if ($expenseTotal == 0 && $incomeTotal == 0 && $savingsTotal == 0) {
            return $bot->sendMessage("📭 Belum ada data transaksi bulan ini.", $bot->chatId());
        }

        // 3. Hitung Cashflow
        // Cashflow = Masuk - (Jajan + Nabung)
        $cashflow = $incomeTotal - $expenseTotal - $savingsTotal;

        // 4. Susun Pesan (Format Baru)
        $msg = "📊 *{$title}*\n";
        $msg .= "💰 Pemasukan: Rp " . number_format($incomeTotal) . "\n";
        $msg .= "💸 Pengeluaran: Rp " . number_format($expenseTotal) . "\n";
        $msg .= "🐖 Tabungan: Rp " . number_format($savingsTotal) . "\n";
        $msg .= "------------------\n";
        
        // Loop Daftar Pengeluaran (Detail)
        foreach ($expenses as $index => $tx) {
            $num = $index + 1; // Nomor urut (1, 2, 3...)
            $desc = Str::limit($tx->description, 18); // Batasi panjang teks
            $val = number_format($tx->amount);
            
            // Format Tanggal: 18 Jan
            // Menggunakan Carbon::parse untuk jaga-jaga jika format di DB string
            $date = Carbon::parse($tx->transaction_date)->translatedFormat('d M'); 

            $msg .= "{$num}. {$desc} : {$val} ({$date})\n";
        }
        
        $msg .= "------------------\n";
        $msg .= "💵 *Sisa/Cashflow: Rp " . number_format($cashflow) . "*";

        $bot->sendMessage($msg, $bot->chatId(), null, 'Markdown');

    } catch (\Throwable $e) {
        $bot->sendMessage("⚠️ Error: " . $e->getMessage());
    }
});

$bot->onCommand('buatwallet {name}', function (Nutgram $bot, $name) {
    $user = User::where('telegram_chat_id', $bot->chatId())->first();
    Wallet::create(['user_id' => $user->id, 'name' => $name, 'type' => 'bank', 'balance' => 0]);
    $bot->sendMessage("✅ Wallet **{$name}** dibuat!", $bot->chatId(), null, 'Markdown');
});

/*
|--------------------------------------------------------------------------
| 6. LOGIC TRANSAKSI PENGELUARAN
|--------------------------------------------------------------------------
*/
$bot->onText('^([0-9]+[kK]?) (.*)', function (Nutgram $bot, $amountStr, $description) {
    try {
        $user = User::where('telegram_chat_id', $bot->chatId())->first();
        if (!$user) return $bot->sendMessage("❌ Akun belum terhubung.");

        $cleanAmount = str_replace('k', '000', strtolower($amountStr));
        if (!is_numeric($cleanAmount)) return $bot->sendMessage("❌ Format angka salah.");

        // Smart Wallet
        $wallets = $user->wallets()->get();
        $selectedWallet = null;
        foreach ($wallets as $w) {
            if (stripos($description, $w->name) !== false) {
                $selectedWallet = $w;
                break;
            }
        }
        if (!$selectedWallet) {
            if ($wallets->isEmpty()) {
                $selectedWallet = Wallet::create(['user_id' => $user->id, 'name' => 'Tunai (Cash)', 'type' => 'cash', 'balance' => 0]);
            } else {
                $selectedWallet = $wallets->first();
            }
        }

        // Mode Edit
        $editingTxId = Cache::get('editing_user_' . $bot->chatId());
        if ($editingTxId) {
            $tx = Transaction::find($editingTxId);
            if (!$tx) { Cache::forget('editing_user_' . $bot->chatId()); return $bot->sendMessage("❌ Transaksi hilang."); }
            
            // Logika Reverse Saldo (Hanya support edit Expense/Income sederhana)
            $oldWallet = Wallet::find($tx->wallet_id);
            if ($oldWallet) {
                 if ($tx->type == 'expense') $oldWallet->increment('balance', $tx->amount);
                 elseif ($tx->type == 'income') $oldWallet->decrement('balance', $tx->amount);
            }

            // Update (Default ke Expense jika teks biasa)
            $tx->update([
                'amount' => $cleanAmount,
                'description' => trim($description),
                'wallet_id' => $selectedWallet->id,
                'transaction_date' => now(),
            ]);
            $selectedWallet->decrement('balance', $cleanAmount);
            Cache::forget('editing_user_' . $bot->chatId());
            $bot->sendMessage("✅ *Data Diperbarui!*\n💸 Rp " . number_format($cleanAmount) . "\n📝 {$description}", $bot->chatId(), null, 'Markdown');

        } else {
            // Mode Create Expense
            Transaction::create([
                'user_id' => $user->id, 'wallet_id' => $selectedWallet->id,
                'type' => 'expense', 'amount' => $cleanAmount,
                'description' => trim($description), 'transaction_date' => now()
            ]);
            $selectedWallet->decrement('balance', $cleanAmount);
            $bot->sendMessage("✅ *Tercatat!* \n💸 Rp " . number_format($cleanAmount, 0, ',', '.') . "\n💳 Via: *{$selectedWallet->name}*", $bot->chatId(), null, 'Markdown');
        }
    } catch (\Throwable $e) { $bot->sendMessage("⚠️ Error: " . $e->getMessage()); }
});