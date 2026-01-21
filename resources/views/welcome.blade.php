<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FinPlan Node</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Manrope', 'sans-serif'] },
                    colors: {
                        grass: {
                            bg: '#141414',
                            panel: '#1F1F1F',
                            border: '#333333',
                            green: '#00E599',
                            text: '#FFFFFF',
                            muted: '#A1A1AA',
                            danger: '#FF4D4D',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { background-color: #000; color: white; }
        .sidebar-item:hover { background-color: #2A2A2A; border-right: 3px solid #00E599; }
        .sidebar-item.active { background-color: #2A2A2A; border-right: 3px solid #00E599; color: #00E599; }
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #141414; }
        ::-webkit-scrollbar-thumb { background: #333; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #00E599; }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-sm font-sans" x-data="{ openModal: false, txType: 'expense' }">

@auth
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" class="fixed top-5 right-5 z-50 bg-grass-green text-black px-6 py-3 rounded shadow-[0_0_20px_rgba(0,229,153,0.4)] font-bold flex items-center animate-bounce">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
        {{ session('success') }}
    </div>
    @endif

    <aside class="w-64 bg-grass-bg border-r border-grass-border flex flex-col hidden md:flex">
        <div class="h-16 flex items-center px-6 border-b border-grass-border">
            <div class="w-8 h-8 rounded-full bg-grass-green flex items-center justify-center mr-3 text-black font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            </div>
            <span class="font-bold text-lg tracking-wide text-white">FinPlan<span class="text-grass-green">.io</span></span>
        </div>
        <nav class="flex-1 py-6 space-y-1">
            <a href="#" class="sidebar-item active flex items-center px-6 py-3 transition-colors">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                Dashboard
            </a>
            <a href="{{ url('/admin') }}" class="sidebar-item flex items-center px-6 py-3 text-grass-muted hover:text-white transition-colors">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                Admin Panel
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full sidebar-item flex items-center px-6 py-3 text-grass-muted hover:text-white text-left transition-colors">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    Logout
                </button>
            </form>
        </nav>
        <div class="p-4 border-t border-grass-border">
            <div class="flex items-center">
                <div class="w-8 h-8 rounded bg-gray-700 flex items-center justify-center font-bold text-xs text-white">{{ substr($user->name, 0, 2) }}</div>
                <div class="ml-3">
                    <p class="text-xs font-bold text-white">{{ $user->name }}</p>
                    <div class="flex items-center mt-1">
                        <div class="w-2 h-2 rounded-full {{ $user->telegram_chat_id ? 'bg-blue-500' : 'bg-grass-green' }} mr-1.5 animate-pulse"></div>
                        <p class="text-[10px] {{ $user->telegram_chat_id ? 'text-blue-400' : 'text-grass-green' }}">{{ $user->telegram_chat_id ? 'TELEGRAM ACTIVE' : 'ONLINE' }}</p>
                    </div>
                    @if($user->telegram_username) <p class="text-[10px] text-grass-muted">@ {{ $user->telegram_username }}</p> @endif
                </div>
            </div>
        </div>
    </aside>

    <main class="flex-1 bg-black flex flex-col relative">
        <div class="flex-1 overflow-y-auto p-6 md:p-10">
            <div class="max-w-6xl mx-auto">
                
                <div class="flex justify-between items-end mb-8">
                    <div>
                        <h1 class="text-2xl font-bold text-white mb-1">Dashboard</h1>
                        <p class="text-grass-muted text-xs flex items-center gap-2">
                            Financial Node Status: Active
                            @if($user->is_verified)
                                <span class="text-grass-green bg-grass-green/10 px-1.5 rounded border border-grass-green/20">VERIFIED</span>
                            @endif
                            @if($user->telegram_chat_id) <span class="text-blue-400 bg-blue-900/20 px-1.5 rounded border border-blue-800/30">BOT LINKED</span> @endif
                        </p>
                    </div>
                    <button @click="openModal = true" class="bg-grass-green text-black px-5 py-2.5 rounded font-bold hover:bg-white transition text-xs flex items-center shadow-[0_0_15px_rgba(0,229,153,0.4)]">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        NEW TRANSACTION
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                    <div class="bg-grass-panel border border-grass-border rounded p-6 relative overflow-hidden">
                        <p class="text-grass-muted text-xs uppercase tracking-wider mb-2">Total Net Worth</p>
                        <h2 class="text-3xl font-bold text-white font-mono">Rp {{ number_format($totalBalance ?? 0, 0, ',', '.') }}</h2>
                    </div>
                    <div class="bg-grass-panel border border-grass-border rounded p-6">
                        <p class="text-grass-muted text-xs uppercase tracking-wider mb-2">Income (Month)</p>
                        <h2 class="text-3xl font-bold text-grass-green font-mono">+ {{ number_format(($income ?? 0) / 1000, 0) }} K</h2>
                    </div>
                    <div class="bg-grass-panel border border-grass-border rounded p-6">
                        <p class="text-grass-muted text-xs uppercase tracking-wider mb-2">Expense (Month)</p>
                        <h2 class="text-3xl font-bold text-grass-danger font-mono">- {{ number_format(($expense ?? 0) / 1000, 0) }} K</h2>
                    </div>
                </div>

                @if($goals->count() > 0)
                <div class="mb-8">
                    <h3 class="font-bold text-white mb-4 flex items-center">
                        <svg class="w-4 h-4 mr-2 text-grass-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        Active Goals / Savings
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($goals as $goal)
                        <div class="bg-grass-panel border border-grass-border rounded p-5 hover:border-grass-green transition-colors duration-300 group relative">
                            @if($goal->is_joint)
                                <span class="absolute top-3 right-3 text-[10px] bg-blue-900/50 text-blue-400 border border-blue-800 px-2 py-0.5 rounded font-mono">JOINT</span>
                            @endif

                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <h4 class="font-bold text-white group-hover:text-grass-green transition">{{ $goal->name }}</h4>
                                    <p class="text-xs text-grass-muted font-mono tracking-wide">CODE: {{ $goal->code }}</p>
                                </div>
                            </div>
                            
                            <div class="flex justify-between items-end mb-2">
                                <span class="text-lg font-bold font-mono text-white">
                                    {{ number_format($goal->current_amount / 1000, 0) }}k
                                </span>
                                <span class="text-xs text-grass-muted">
                                    / {{ number_format($goal->target_amount / 1000, 0) }}k
                                </span>
                            </div>

                            <div class="w-full bg-[#111] h-1.5 rounded-full overflow-hidden border border-[#333]">
                                <div class="bg-grass-green h-full transition-all duration-1000" style="width: {{ $goal->progress }}%"></div>
                            </div>
                            <div class="text-right mt-1">
                                <span class="text-[10px] text-grass-green font-mono">{{ $goal->progress }}%</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <div class="bg-grass-panel border border-grass-border rounded overflow-hidden">
                    <div class="px-6 py-4 border-b border-grass-border flex justify-between items-center bg-[#181818]">
                        <h3 class="font-bold text-white">Recent Transactions</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="text-xs text-grass-muted uppercase bg-[#181818]">
                                <tr>
                                    <th class="px-6 py-3">Desc</th>
                                    <th class="px-6 py-3">Wallet</th>
                                    <th class="px-6 py-3 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-grass-border">
                                @forelse($recentTransactions as $tx)
                                <tr class="hover:bg-[#252525] transition-colors">
                                    <td class="px-6 py-3">
                                        <div class="font-medium text-white">{{ $tx->description }}</div>
                                        <div class="text-[10px] text-grass-muted">{{ $tx->transaction_date ? $tx->transaction_date->format('d M') : '-' }}</div>
                                    </td>
                                    <td class="px-6 py-3 text-grass-muted text-xs">{{ $tx->wallet->name ?? '-' }}</td>
                                    <td class="px-6 py-3 text-right font-mono font-bold text-xs {{ $tx->type == 'income' ? 'text-grass-green' : 'text-white' }}">
                                        {{ $tx->type == 'income' ? '+' : '-' }} {{ number_format($tx->amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="px-6 py-8 text-center text-grass-muted">No data yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <div x-cloak x-show="openModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm">
        <div @click.away="openModal = false" class="bg-grass-panel border border-grass-border w-full max-w-md rounded-lg shadow-2xl p-6">
            <h3 class="text-xl font-bold text-white mb-6">New Transaction</h3>
            <form action="{{ route('transaction.store') }}" method="POST">
                @csrf
                <div class="flex bg-grass-bg rounded p-1 mb-4 border border-grass-border">
                    <button type="button" @click="txType = 'expense'" :class="txType === 'expense' ? 'bg-grass-danger text-white' : 'text-grass-muted'" class="flex-1 py-2 text-xs font-bold rounded">EXPENSE</button>
                    <button type="button" @click="txType = 'income'" :class="txType === 'income' ? 'bg-grass-green text-black' : 'text-grass-muted'" class="flex-1 py-2 text-xs font-bold rounded">INCOME</button>
                </div>
                <input type="hidden" name="type" x-model="txType">
                <input type="number" name="amount" required class="w-full bg-grass-bg border border-grass-border text-white px-4 py-3 rounded mb-4" placeholder="Amount (Rp)">
                <input type="text" name="description" required placeholder="Description" class="w-full bg-grass-bg border border-grass-border text-white px-4 py-3 rounded mb-4">
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <select name="wallet_id" class="w-full bg-grass-bg border border-grass-border text-white px-4 py-3 rounded">
                        @foreach($wallets as $w) <option value="{{ $w->id }}">{{ $w->name }}</option> @endforeach
                    </select>
                    <input type="date" name="date" value="{{ date('Y-m-d') }}" class="w-full bg-grass-bg border border-grass-border text-white px-4 py-3 rounded">
                </div>
                <button type="submit" class="w-full bg-grass-green hover:bg-white text-black font-bold py-3 rounded">CONFIRM</button>
            </form>
        </div>
    </div>

@else
    <div class="flex items-center justify-center w-full h-full bg-black relative">
        <div class="absolute inset-0 z-0" style="background-image: radial-gradient(#333 1px, transparent 1px); background-size: 20px 20px; opacity: 0.2;"></div>
        <div class="relative z-10 w-full max-w-md p-8 bg-grass-panel border border-grass-border rounded-lg shadow-2xl text-center">
            <h1 class="text-2xl font-bold text-white mb-8">FinPlan Node Access</h1>
            <div class="space-y-4">
                <a href="{{ url('/admin/login') }}" class="block w-full py-3 px-4 bg-grass-green hover:bg-white text-black font-bold rounded transition">ACCESS DASHBOARD</a>
                <a href="https://t.me/BotTabunganKamuBot" class="block w-full py-3 px-4 border border-grass-border text-grass-muted hover:text-white rounded transition">TELEGRAM BOT</a>
            </div>
        </div>
    </div>
@endauth

</body>
</html>