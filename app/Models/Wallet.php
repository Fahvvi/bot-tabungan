<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $guarded = []; // Cara cepat agar semua kolom bisa diisi

public function user()
{
    return $this->belongsTo(User::class);
}
}
