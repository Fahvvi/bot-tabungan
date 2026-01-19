<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser; // 1. Import Ini
use Filament\Panel; // 2. Import Ini
use App\Notifications\CustomResetPassword;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'telegram_chat_id', // <--- Tambahkan ini
        'telegram_username', // <--- Tambahkan ini (opsional)
        'is_verified',       // Baru
        'verification_code', // Baru
        'default_wallet_id'  // Baru
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Send the password reset notification.
     * Override method bawaan Laravel.
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new CustomResetPassword($token));
    }
    public function canAccessPanel(Panel $panel): bool
    {
        // Return true agar SEMUA user yang login bisa masuk dashboard
        // Nanti bisa diubah logikanya (misal hanya email tertentu)
        return true; 
    }

    public function wallets()
{
    return $this->hasMany(Wallet::class);
}
public function defaultWallet()
{
    return $this->belongsTo(Wallet::class, 'default_wallet_id');
}

// Goals di mana user ini adalah Member (termasuk Owner)
// User bisa punya banyak goal (milik sendiri atau gabungan)
public function goals()
{
    return $this->belongsToMany(Goal::class, 'goal_user');
}
}
