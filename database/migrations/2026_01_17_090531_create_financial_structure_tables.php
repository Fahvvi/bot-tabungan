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
    // 1. Modifikasi tabel USERS (tambah telegram ID)
    Schema::table('users', function (Blueprint $table) {
        $table->string('telegram_chat_id')->nullable()->unique()->after('email');
        $table->string('telegram_username')->nullable()->after('telegram_chat_id');
    });

    // 2. Tabel WALLETS (Sumber dana: Cash, Bank BCA, GoPay, dll)
    Schema::create('wallets', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->string('name'); // Misal: BCA Utama, Dompet Cash
        $table->string('type')->default('bank'); // bank, ewallet, cash
        $table->decimal('balance', 15, 2)->default(0);
        $table->timestamps();
    });

    // 3. Tabel GOALS (Tabungan Bersama)
    Schema::create('goals', function (Blueprint $table) {
        $table->id();
        $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete(); // Pembuat Goal
        $table->string('name'); // Misal: Tabungan Nikah
        $table->decimal('target_amount', 15, 2);
        $table->decimal('current_amount', 15, 2)->default(0);
        $table->date('deadline')->nullable();
        $table->string('status')->default('active'); // active, reached, cancelled
        $table->timestamps();
    });

    // 4. Tabel GOAL_MEMBERS (Pivot untuk kolaborasi)
    Schema::create('goal_members', function (Blueprint $table) {
        $table->id();
        $table->foreignId('goal_id')->constrained()->cascadeOnDelete();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->string('role')->default('member'); // owner, member
        $table->timestamps();
    });

    // 5. Tabel TRANSACTIONS (Pencatat utama)
    Schema::create('transactions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Siapa yang input
        
        // Relasi opsional: Bisa expense personal (wallet) atau nabung (goal)
        $table->foreignId('wallet_id')->nullable()->constrained()->nullOnDelete();
        $table->foreignId('goal_id')->nullable()->constrained()->nullOnDelete();
        
        $table->string('type'); // expense, income, transfer_to_goal
        $table->decimal('amount', 15, 2);
        $table->string('category')->nullable(); // Makan, Transport, Gaji
        $table->text('description')->nullable();
        $table->date('transaction_date');
        $table->timestamps();
    });
}
};
