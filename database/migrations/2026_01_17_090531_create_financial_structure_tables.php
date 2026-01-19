<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Modifikasi tabel USERS (Fitur Verifikasi & Telegram)
        Schema::table('users', function (Blueprint $table) {
            $table->string('telegram_chat_id')->nullable()->unique()->after('email');
            $table->string('telegram_username')->nullable()->after('telegram_chat_id');
            
            // Kolom untuk Verifikasi Bot (Anti-Spam)
            $table->boolean('is_verified')->default(false);
            $table->string('verification_code')->nullable();
        });

        // 2. Tabel WALLETS (Dompet)
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // Contoh: BCA, Tunai
            $table->string('type')->default('bank'); // bank, cash
            $table->decimal('balance', 15, 2)->default(0);
            $table->timestamps();

            // CONSTRAINT UNIQUE: Mencegah user punya 2 dompet bernama sama
            $table->unique(['user_id', 'name']); 
        });

        // 2.5 Tambah Default Wallet ke Users (Setelah tabel wallets ada)
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('default_wallet_id')->nullable()->constrained('wallets')->nullOnDelete();
        });

        // 3. Tabel GOALS (Fitur Collab sudah masuk di sini)
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            
            // ✅ INI BAGIAN COLLAB: Kolom Code invite link
            $table->string('code')->unique()->nullable(); 
            
            $table->decimal('target_amount', 15, 2);
            $table->decimal('current_amount', 15, 2)->default(0);
            $table->date('deadline')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        // 4. Tabel Pivot GOAL_USER (✅ INI BAGIAN COLLAB: Pivot Table)
        Schema::create('goal_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('member'); // owner, member
            $table->timestamps();
        });

        // 5. Tabel TRANSACTIONS
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            $table->foreignId('wallet_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('goal_id')->nullable()->constrained()->nullOnDelete();
            
            $table->string('type'); // expense, income, transfer_to_goal
            $table->decimal('amount', 15, 2);
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->date('transaction_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('goal_user');
        Schema::dropIfExists('goals');
        
        if (Schema::hasColumn('users', 'default_wallet_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['default_wallet_id']);
                $table->dropColumn('default_wallet_id');
            });
        }

        Schema::dropIfExists('wallets');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'telegram_chat_id', 
                'telegram_username', 
                'is_verified', 
                'verification_code'
            ]);
        });
    }
};