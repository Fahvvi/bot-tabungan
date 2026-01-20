<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// 1. Import Wajib untuk Filament
use Filament\Models\Contracts\FilamentUser; 
use Filament\Panel;

// Import Notification Custom
use App\Notifications\CustomResetPassword; 

// 2. Pastikan ada "implements FilamentUser"
class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'telegram_chat_id',
        'is_verified',
        'verification_code',
        'default_wallet_id'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_verified' => 'boolean',
        ];
    }

    public function wallets()
    {
        return $this->hasMany(Wallet::class);
    }

    public function goals()
    {
        return $this->belongsToMany(Goal::class, 'goal_user');
    }

    public function defaultWallet()
    {
        return $this->belongsTo(Wallet::class, 'default_wallet_id');
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new CustomResetPassword($token));
    }

    // 3. Method Izin Akses (WAJIB ADA & RETRUN TRUE)
    public function canAccessPanel(Panel $panel): bool
    {
        return true; 
    }
}