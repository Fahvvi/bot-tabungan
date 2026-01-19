<?php

/** @var SergiX44\Nutgram\Nutgram $bot */

use App\Models\User;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Models\Goal;
use App\Exports\TransactionsExport;
use Maatwebsite\Excel\Facades\Excel;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\QueryException; 
use Illuminate\Support\Facades\Password; // ✅ Wajib import ini untuk fitur email

/*
|--------------------------------------------------------------------------
| 0. SYSTEM & MIDDLEWARE
|--------------------------------------------------------------------------
*/
$bot->middleware(function (Nutgram $bot, $next) {
    echo "[LOG] " . $bot->message()->text . "\n";
    $next($bot);
});

// Middleware: Cek Verifikasi User
$bot->middleware(function (Nutgram $bot, $next) {
    $text = $bot->message()->text ?? '';
    // Izinkan command tertentu tanpa verifikasi
    if (str_starts_with($text, '/start') || str_starts_with($text, '/verif') || str_starts_with($text, '/ping')) {
        $next($bot);
        return;
    }

    $user = User::where('telegram_chat_id', $bot->chatId())->first();
    
    // Blokir jika belum verified
    if (!$user || !$user->is_verified) {
        $bot->sendMessage("🔒 *Akses Terkunci!*\nKamu belum verifikasi.\nKetik `/start` untuk mendapatkan kode.", $bot->chatId(), null, 'Markdown');
        return;
    }

    $next($bot);
});

$bot->onCommand('ping', function (Nutgram $bot) {
    $bot->sendMessage("Pong! 🏓 Bot Aktif.");
});

/*
|--------------------------------------------------------------------------
| 1. REGISTRASI & VERIFIKASI
|--------------------------------------------------------------------------
*/
$bot->onCommand('start', function (Nutgram $bot) {
    $chatId = $bot->chatId();
    $name = $bot->user()->first_name ?? 'User';
    
    // Auto Register
    $user = User::firstOrCreate(
        ['telegram_chat_id' => $chatId],
        ['name' => $name, 'email' => "{$chatId}@bot.com", 'password' => bcrypt(Str::random(16))]
    );

    // Jika sudah verified
    if ($user->is_verified) {
        $msg = "👋 *Halo, {$user->name}!*\n\n";
        $msg .= "💰 *Keuangan*\n• `/masuk 10jt Gaji`\n• `25k Nasi Padang`\n• `/edit` | `/export`\n\n";
        $msg .= "💳 *Dompet*\n• `/buatwallet BCA`\n• `/setdefault BCA`\n\n";
        $msg .= "🌐 *Akun Web*\n• `/verified email@mu.com` (Akses Dashboard)\n\n"; // Menu Baru
        $msg .= "📊 *Laporan*\n• `/rekap` (Detail)";
        return $bot->sendMessage($msg, $chatId, null, 'Markdown');
    }

    // Generate Kode Verifikasi
    $code = strtoupper(Str::random(6));
    $user->update(['verification_code' => $code]);

    $msg = "🔐 *Verifikasi Keamanan*\n\n";
    $msg .= "Halo {$name}, silakan ketik kode ini untuk mengaktifkan bot:\n\n";
    $msg .= "`{$code}`\n\n";
    $msg .= "👉 Balas dengan: `/verif {$code}`";

    $bot->sendMessage($msg, $chatId, null, 'Markdown');
});

$bot->onCommand('verif {code}', function (Nutgram $bot, $code) {
    $user = User::where('telegram_chat_id', $bot->chatId())->first();
    if (!$user) return;

    if ($user->is_verified) return $bot->sendMessage("✅ Akunmu sudah aktif.");

    if (strtoupper(trim($code)) === $user->verification_code) {
        $user->update(['is_verified' => true, 'verification_code' => null]);
        
        // Buat wallet default 'Tunai' jika belum ada
        if ($user->wallets()->count() == 0) {
            $w = Wallet::create(['user_id' => $user->id, 'name' => 'Tunai', 'type' => 'cash', 'balance' => 0]);
            $user->update(['default_wallet_id' => $w->id]);
        }

        $bot->sendMessage("🎉 *Verifikasi Berhasil!*\nSilakan gunakan bot sekarang.\nCoba ketik: `/buatwallet BCA`", $bot->chatId(), null, 'Markdown');
    } else {
        $bot->sendMessage("❌ Kode salah!");
    }
});

/*
|--------------------------------------------------------------------------
| 1.5 FITUR AKSES DASHBOARD WEB (BARU!)
|--------------------------------------------------------------------------
*/
$bot->onCommand('verified {email}', function (Nutgram $bot, $email) {
    $user = User::where('telegram_chat_id', $bot->chatId())->first();
    if (!$user) return $bot->sendMessage("❌ Error: User tidak ditemukan.");

    // 1. Validasi Format Email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $bot->sendMessage("❌ Format email salah.\nContoh: `/verified nama@gmail.com`", $bot->chatId(), null, 'Markdown');
    }

    // 2. Cek Email Duplikat (Agar tidak menimpa akun orang lain)
    if (User::where('email', $email)->where('id', '!=', $user->id)->exists()) {
        return $bot->sendMessage("❌ Email **{$email}** sudah digunakan oleh user lain.", $bot->chatId(), null, 'Markdown');
    }

    $bot->sendMessage("⏳ Memproses email **{$email}**...", $bot->chatId(), null, 'Markdown');

    // 3. Update Email & Kirim Reset Link
    try {
        $user->update(['email' => $email]);

        // Mengirim Link Reset Password bawaan Laravel
        // Pastikan setting MAIL di .env sudah benar!
        $status = Password::sendResetLink(['email' => $email]);

        if ($status === Password::RESET_LINK_SENT) {
            $msg = "✅ **Email Terkirim!**\n\n";
            $msg .= "Silakan cek Inbox/Spam email **{$email}**.\n";
            $msg .= "Klik tombol di email untuk membuat **Password Baru**.\n\n";
            $msg .= "Setelah itu, login ke Dashboard Web menggunakan email & password tersebut.";
            $bot->sendMessage($msg, $bot->chatId(), null, 'Markdown');
        } else {
            $bot->sendMessage("⚠️ Gagal mengirim email. Mohon hubungi admin untuk cek konfigurasi SMTP.", $bot->chatId());
        }

    } catch (\Throwable $e) {
        $bot->sendMessage("⚠️ Error: " . $e->getMessage(), $bot->chatId());
    }
});

/*
|--------------------------------------------------------------------------
| 2. MANAJEMEN DOMPET (SAFE MODE)
|--------------------------------------------------------------------------
*/
$bot->onCommand('buatwallet {name}', function (Nutgram $bot, $name) {
    $user = User::where('telegram_chat_id', $bot->chatId())->first();
    
    try {
        $w = Wallet::create(['user_id' => $user->id, 'name' => $name, 'type' => 'bank', 'balance' => 0]);
        
        if (!$user->default_wallet_id) $user->update(['default_wallet_id' => $w->id]);

        $bot->sendMessage("✅ Wallet **{$name}** dibuat! \nJadikan default? Ketik: `/setdefault {$name}`", $bot->chatId(), null, 'Markdown');
        
    } catch (QueryException $e) {
        if ($e->errorInfo[1] == 1062) {
            $bot->sendMessage("❌ **Gagal!** Dompet **{$name}** sudah ada.\nGunakan nama lain.", $bot->chatId(), null, 'Markdown');
        } else {
            $bot->sendMessage("⚠️ Error database.", $bot->chatId());
        }
    } catch (\Throwable $e) {
        $bot->sendMessage("⚠️ Error: " . $e->getMessage(), $bot->chatId());
    }
});

$bot->onCommand('setdefault {name}', function (Nutgram $bot, $name) {
    $user = User::where('telegram_chat_id', $bot->chatId())->first();
    $wallet = $user->wallets()->where('name', 'LIKE', "%{$name}%")->first();
    
    if (!$wallet) return $bot->sendMessage("❌ Wallet '{$name}' tidak ditemukan.");

    $user->update(['default_wallet_id' => $wallet->id]);
    $bot->sendMessage("✅ Default wallet diubah ke: **{$wallet->name}**", $bot->chatId(), null, 'Markdown');

    // TAMPILKAN MENU LAGI
    $msg = "\n==========================\n";
    $msg .= "👋 *Menu Utama*\n\n";
    $msg .= "💰 *Keuangan*\n• `/masuk 10jt Gaji`\n• `25k Nasi Padang`\n• `/edit` | `/export`\n\n";
    $msg .= "💳 *Dompet*\n• `/buatwallet BCA`\n• `/setdefault BCA`\n\n";
    $msg .= "🌐 *Akun Web*\n• `/verified email@mu.com`\n\n";
    $msg .= "📊 *Laporan*\n• `/rekap` (Detail)";
    
    $bot->sendMessage($msg, $bot->chatId(), null, 'Markdown');
});

/*
|--------------------------------------------------------------------------
| 3. LOGIC TRANSAKSI (SMART WALLET + SALDO INFO)
|--------------------------------------------------------------------------
*/

// Helper: Cari Wallet (Default / Override [BCA])
function resolveWallet($user, $text) {
    $targetWallet = null;
    $cleanText = $text;

    // Cek Override [NamaWallet]
    if (preg_match('/\[(.*?)\]/', $text, $matches)) {
        $walletName = $matches[1];
        $targetWallet = $user->wallets()->where('name', 'LIKE', "%{$walletName}%")->first();
        $cleanText = trim(str_replace($matches[0], '', $text));
    }

    // Jika tidak ada override, pakai Default
    if (!$targetWallet) {
        if ($user->default_wallet_id) {
            $targetWallet = Wallet::find($user->default_wallet_id);
        } else {
            // Fallback ke Tunai/Pertama
            $targetWallet = $user->wallets()->first();
        }
    }
    return [$targetWallet, $cleanText];
}

// HANDLER PEMASUKAN: /masuk 10jt Gaji [BCA]
$bot->onText('^/(?:masuk|pemasukan)\s+([0-9.,]+[a-zA-Z]*)\s+(.*)', function (Nutgram $bot, $amtStr, $rawDesc) {
    try {
        $user = User::where('telegram_chat_id', $bot->chatId())->first();
        
        $cleanStr = str_replace([',', '.'], '', strtolower($amtStr)); 
        $amount = str_ireplace(['k', 'jt', 'juta', 'm'], ['000', '000000', '000000', '000000'], $cleanStr);
        if (!is_numeric($amount)) return $bot->sendMessage("❌ Format salah.");

        list($wallet, $desc) = resolveWallet($user, $rawDesc);
        if (!$wallet) return $bot->sendMessage("❌ Kamu belum punya wallet. Ketik `/buatwallet Tunai`");

        DB::transaction(function () use ($user, $wallet, $amount, $desc) {
            Transaction::create([
                'user_id' => $user->id, 'wallet_id' => $wallet->id, 'type' => 'income',
                'amount' => $amount, 'description' => $desc, 'transaction_date' => now()
            ]);
            $wallet->increment('balance', $amount);
        });
        
        $wallet->refresh();
        $bot->sendMessage(
            "💰 *Pemasukan!*\n➕ Rp " . number_format($amount) . "\n📝 {$desc}\n💳 Ke: **{$wallet->name}**\n💰 Saldo: Rp " . number_format($wallet->balance), 
            $bot->chatId(), null, 'Markdown'
        );

    } catch (\Throwable $e) { $bot->sendMessage("Error: " . $e->getMessage()); }
});

// HANDLER PENGELUARAN (TEXT BIASA): 25k Kopi [Gopay]
$bot->onText('^([0-9.,]+[a-zA-Z]*)\s+(.*)', function (Nutgram $bot, $amtStr, $rawDesc) {
    if (str_starts_with($amtStr, '/')) return; // Skip command

    try {
        $user = User::where('telegram_chat_id', $bot->chatId())->first();

        $cleanStr = str_replace([',', '.'], '', strtolower($amtStr)); 
        $amount = str_ireplace(['k', 'jt', 'juta', 'm'], ['000', '000000', '000000', '000000'], $cleanStr);
        if (!is_numeric($amount)) return $bot->sendMessage("❌ Format angka salah.");

        list($wallet, $desc) = resolveWallet($user, $rawDesc);
        if (!$wallet) return $bot->sendMessage("❌ Belum ada wallet.");

        // Cek Mode Edit
        $editingTxId = Cache::get('editing_user_' . $bot->chatId());
        if ($editingTxId) {
            $tx = Transaction::find($editingTxId);
            if ($tx) {
                // Logic Reverse Saldo
                $oldWallet = Wallet::find($tx->wallet_id);
                if ($oldWallet) {
                    if($tx->type == 'expense') $oldWallet->increment('balance', $tx->amount);
                    else $oldWallet->decrement('balance', $tx->amount);
                }
                $tx->update(['amount'=>$amount, 'description'=>$desc, 'wallet_id'=>$wallet->id, 'transaction_date'=>now()]);
                $wallet->decrement('balance', $amount);
                Cache::forget('editing_user_' . $bot->chatId());
                return $bot->sendMessage("✅ *Data Diperbarui!*", $bot->chatId(), null, 'Markdown');
            }
        }

        // CREATE Expense
        Transaction::create([
            'user_id' => $user->id, 'wallet_id' => $wallet->id, 'type' => 'expense',
            'amount' => $amount, 'description' => $desc, 'transaction_date' => now()
        ]);
        $wallet->decrement('balance', $amount);
        
        $wallet->refresh();
        $bot->sendMessage(
            "💸 *Tercatat!*\n➖ Rp " . number_format($amount) . "\n📝 {$desc}\n💳 Via: **{$wallet->name}**\n💰 Sisa Saldo: Rp " . number_format($wallet->balance), 
            $bot->chatId(), null, 'Markdown'
        );

    } catch (\Throwable $e) { $bot->sendMessage("Error: " . $e->getMessage()); }
});

/*
|--------------------------------------------------------------------------
| 4. FITUR REKAP (URUT TANGGAL)
|--------------------------------------------------------------------------
*/
$bot->onCommand('rekap', function (Nutgram $bot) {
    try {
        $user = User::where('telegram_chat_id', $bot->chatId())->first();
        if (!$user) return $bot->sendMessage("❌ Akun belum terhubung.");

        $text = $bot->message()->text;
        $params = trim(str_replace('/rekap', '', $text));

        if (!empty($params)) {
            $dates = explode(' ', $params);
            if (count($dates) != 2) return $bot->sendMessage("❌ Format salah.");
            $start = Carbon::createFromFormat('d-m-Y', $dates[0])->startOfDay();
            $end = Carbon::createFromFormat('d-m-Y', $dates[1])->endOfDay();
            $title = "Laporan ({$dates[0]} - {$dates[1]})";
        } else {
            $start = Carbon::now()->startOfMonth();
            $end = Carbon::now()->endOfMonth();
            $title = "Bulan Ini (" . Carbon::now()->translatedFormat('M Y') . ")";
        }

        $expenses = Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$start, $end])
            ->orderBy('transaction_date', 'asc') // Urut Tanggal
            ->get();

        $incomeTotal = Transaction::where('user_id', $user->id)->where('type', 'income')->whereBetween('transaction_date', [$start, $end])->sum('amount');
        $savingsTotal = Transaction::where('user_id', $user->id)->where('type', 'transfer_to_goal')->whereBetween('transaction_date', [$start, $end])->sum('amount');
        $expenseTotal = $expenses->sum('amount');

        if ($expenseTotal == 0 && $incomeTotal == 0 && $savingsTotal == 0) return $bot->sendMessage("📭 Data kosong.", $bot->chatId());

        $cashflow = $incomeTotal - $expenseTotal - $savingsTotal;

        $msg = "📊 *{$title}*\n";
        $msg .= "💰 Pemasukan: Rp " . number_format($incomeTotal) . "\n";
        $msg .= "💸 Pengeluaran: Rp " . number_format($expenseTotal) . "\n";
        $msg .= "🐖 Tabungan: Rp " . number_format($savingsTotal) . "\n";
        $msg .= "------------------\n";
        
        foreach ($expenses as $i => $tx) {
            $date = Carbon::parse($tx->transaction_date)->format('d M');
            $msg .= ($i+1) . ". " . Str::limit($tx->description, 18) . " : " . number_format($tx->amount) . " ({$date})\n";
        }
        
        $msg .= "------------------\n";
        $msg .= "💵 *Sisa/Cashflow: Rp " . number_format($cashflow) . "*";

        $bot->sendMessage($msg, $bot->chatId(), null, 'Markdown');

    } catch (\Throwable $e) { $bot->sendMessage("Error: ".$e->getMessage()); }
});

/*
|--------------------------------------------------------------------------
| 5. FITUR EXPORT EXCEL
|--------------------------------------------------------------------------
*/
$bot->onCommand('export {params?}', function (Nutgram $bot, $params = null) {
    $user = User::where('telegram_chat_id', $bot->chatId())->first();
    
    $start = Carbon::now()->startOfMonth();
    $end = Carbon::now()->endOfMonth();
    $label = "Bulan_Ini";

    if ($params) {
        $dates = explode(' ', $params);
        if (count($dates) == 2) {
            try {
                $start = Carbon::createFromFormat('d-m-Y', $dates[0])->startOfDay();
                $end = Carbon::createFromFormat('d-m-Y', $dates[1])->endOfDay();
                $label = "{$dates[0]}_{$dates[1]}";
            } catch (\Throwable $th) { return $bot->sendMessage("❌ Format tgl salah."); }
        }
    }

    $bot->sendMessage("⏳ Memproses Excel...", $bot->chatId());

    try {
        $fileName = "Laporan_{$user->id}_{$label}.xlsx";
        Excel::store(new TransactionsExport($user->id, $start, $end), $fileName, 'public');
        $filePath = storage_path('app/public/' . $fileName);
        
        if (file_exists($filePath)) {
            $file = fopen($filePath, 'r+');
            $bot->sendDocument($file, ['chat_id' => $bot->chatId(), 'caption' => "📊 Laporan Keuangan ($label)"]);
            fclose($file);
            unlink($filePath);
        } else {
            $bot->sendMessage("❌ Gagal membuat file.");
        }
    } catch (\Throwable $e) { $bot->sendMessage("⚠️ Error Export: " . $e->getMessage()); }
});

/*
|--------------------------------------------------------------------------
| 6. FITUR GOALS & NABUNG (LOGIC FIX + TAMPILAN BARU)
|--------------------------------------------------------------------------
*/
$bot->onCommand('buatgoal {target} {name}', function (Nutgram $bot, $targetStr, $name) {
    $user = User::where('telegram_chat_id', $bot->chatId())->first();
    $target = str_ireplace(['k', 'jt', 'juta'], ['000', '000000', '000000'], strtolower($targetStr));
    if (!is_numeric($target)) return $bot->sendMessage("❌ Format salah.");
    $code = strtoupper(Str::random(6));

    try {
        DB::transaction(function () use ($user, $target, $name, $code) {
            $goal = Goal::create([ 'owner_id' => $user->id, 'name' => $name, 'target_amount' => $target, 'current_amount' => 0, 'code' => $code ]);
            $goal->users()->attach($user->id);
        });
        $bot->sendMessage("✅ Goal **{$name}** dibuat!\nKode: `{$code}`", $bot->chatId(), null, 'Markdown');
    } catch (\Throwable $e) { $bot->sendMessage("Gagal: ".$e->getMessage()); }
});

$bot->onCommand('gabung {code}', function (Nutgram $bot, $code) {
    $user = User::where('telegram_chat_id', $bot->chatId())->first();
    $goal = Goal::where('code', strtoupper($code))->first();
    if (!$goal) return $bot->sendMessage("❌ Kode salah.");
    if ($goal->users()->where('user_id', $user->id)->exists()) return $bot->sendMessage("⚠️ Sudah gabung.");
    $goal->users()->attach($user->id);
    $bot->sendMessage("✅ Berhasil gabung ke **{$goal->name}**!");
});

$bot->onCommand('goals', function (Nutgram $bot) {
    $user = User::where('telegram_chat_id', $bot->chatId())->first();
    $goals = $user->goals()->get();

    if ($goals->isEmpty()) {
        return $bot->sendMessage("📭 Belum ada Goal. \nBuat baru dengan: `/buatgoal 50jt Nikah`", $bot->chatId(), null, 'Markdown');
    }

    $msg = "🎯 *List Goals & Tabungan:*\n";
    
    foreach ($goals as $g) {
        $pct = $g->target_amount > 0 ? ($g->current_amount / $g->target_amount) * 100 : 0;
        $pctCap = min($pct, 100); 
        $bar = str_repeat('🟩', floor($pctCap/10)) . str_repeat('⬜', 10-floor($pctCap/10));
        
        $curr = number_format($g->current_amount);
        $target = number_format($g->target_amount);

        $msg .= "\n📌 *{$g->name}* (`{$g->code}`)\n";
        $msg .= "💰 Rp {$curr} / Rp {$target}\n"; 
        $msg .= "📊 {$bar} (" . round($pct) . "%)\n";
    }
    
    $bot->sendMessage($msg, $bot->chatId(), null, 'Markdown');
});

// FITUR NABUNG (LOGIC FIX & SINKRONISASI SALDO)
$bot->onText('^/nabung\s+([0-9.,]+[a-zA-Z]*)\s+(.*)', function (Nutgram $bot, $amtStr, $rawString) {
    try {
        $user = User::where('telegram_chat_id', $bot->chatId())->first();
        if (!$user) return $bot->sendMessage("❌ Akun belum terhubung.");

        $cleanStr = str_replace([',', '.'], '', strtolower($amtStr)); 
        $amount = str_ireplace(['k', 'jt', 'juta', 'm'], ['000', '000000', '000000', '000000'], $cleanStr);
        if (!is_numeric($amount)) return $bot->sendMessage("❌ Format nominal salah.");

        $targetWallet = null;
        $goalName = trim($rawString);

        if (preg_match('/\[(.*?)\]/', $rawString, $matches)) {
            $walletName = $matches[1];
            $targetWallet = $user->wallets()->where('name', 'LIKE', "%{$walletName}%")->first();
            $goalName = trim(str_replace($matches[0], '', $rawString));
        }

        if (!$targetWallet) {
            $targetWallet = $user->default_wallet_id ? Wallet::find($user->default_wallet_id) : $user->wallets()->first();
        }

        if (!$targetWallet) return $bot->sendMessage("❌ Kamu belum punya dompet. Buat dulu: `/buatwallet Blu`");

        $targetWallet->refresh(); // Refresh Saldo

        if ($targetWallet->balance < $amount) {
            return $bot->sendMessage(
                "⛔ *Transaksi Ditolak!*\n\n" .
                "💳 Sumber Dana: **{$targetWallet->name}**\n" .
                "💰 Saldo Fisik: Rp " . number_format($targetWallet->balance) . "\n" .
                "💸 Ingin Nabung: Rp " . number_format($amount) . "\n\n" .
                "💡 _Tips: Jika uangmu ada di dompet lain, ketik:_\n" .
                "`/nabung " . $amtStr . " " . $goalName . " [NamaDompet]`", 
                $bot->chatId(), null, 'Markdown'
            );
        }

        $goal = $user->goals()->where('name', 'LIKE', "%{$goalName}%")->first();
        if (!$goal) return $bot->sendMessage("❌ Goal **'{$goalName}'** tidak ditemukan.");

        DB::transaction(function () use ($user, $targetWallet, $goal, $amount) {
            Transaction::create([
                'user_id' => $user->id,
                'wallet_id' => $targetWallet->id,
                'goal_id' => $goal->id,
                'type' => 'transfer_to_goal',
                'amount' => $amount,
                'description' => "Nabung {$goal->name}",
                'transaction_date' => now()
            ]);
            
            $targetWallet->decrement('balance', $amount);
            $goal->increment('current_amount', $amount);
        });

        $targetWallet->refresh();
        $bot->sendMessage(
            "✅ *Berhasil Nabung!*\n" .
            "📥 Masuk Goal: **{$goal->name}**\n" .
            "💰 Nominal: Rp " . number_format($amount) . "\n" .
            "💳 Sumber: **{$targetWallet->name}**\n" .
            "📉 Sisa Saldo: Rp " . number_format($targetWallet->balance),
            $bot->chatId(), null, 'Markdown'
        );

    } catch (\Throwable $e) { $bot->sendMessage("⚠️ Error: " . $e->getMessage(), $bot->chatId()); }
});

/*
|--------------------------------------------------------------------------
| 7. FITUR EDIT & BATAL
|--------------------------------------------------------------------------
*/
$bot->onCommand('edit', function (Nutgram $bot) {
    $user = User::where('telegram_chat_id', $bot->chatId())->first();
    $latest = Transaction::where('user_id', $user->id)->latest()->take(3)->get();
    if ($latest->isEmpty()) return $bot->sendMessage("📭 Kosong.");
    $keyboard = InlineKeyboardMarkup::make();
    foreach ($latest as $tx) {
        $icon = match($tx->type) { 'income'=>'➕', 'transfer_to_goal'=>'🐖', default=>'➖' };
        $keyboard->addRow(InlineKeyboardButton::make("{$icon} ".number_format($tx->amount/1000)."k ".Str::limit($tx->description, 12), callback_data: "edit_tx|{$tx->id}"));
    }
    $bot->sendMessage("✏️ Pilih Edit:", $bot->chatId(), null, reply_markup: $keyboard);
});

$bot->onCallbackQueryData('edit_tx|{id}', function (Nutgram $bot, $id) {
    if (empty($id)) { $parts = explode('|', $bot->callbackQuery()->data); $id = $parts[1] ?? null; }
    Cache::put('editing_user_' . $bot->chatId(), $id, 300);
    $bot->sendMessage("📝 Mode Edit Aktif! Ketik nominal & ket baru.", $bot->chatId());
});

$bot->onCommand('batal', function (Nutgram $bot) {
    Cache::forget('editing_user_' . $bot->chatId());
    $bot->sendMessage("✅ Batal Edit.");
});