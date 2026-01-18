<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Goal extends Model
{
    protected $fillable = ['owner_id', 'name', 'target_amount', 'current_amount', 'code'];
    // pastikan 'code' dan 'user_id' ada di sini (user_id biarkan sbg creator)

public function users()
{
    return $this->belongsToMany(User::class, 'goal_user');
}
}
