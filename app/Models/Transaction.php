<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $guarded = [];

protected $casts = [
    'transaction_date' => 'date',
];

public function wallet()
{
    return $this->belongsTo(Wallet::class);
}

public function goal()
{
    return $this->belongsTo(Goal::class);
}
}
