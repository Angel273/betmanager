<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BetPath extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'start_amount',
        'target_amount',
        'reinvestment_rate',
        'current_step',
        'total_steps',
        'status'
    ];

    protected $casts = [
        'start_amount' => 'decimal:2',
        'target_amount' => 'decimal:2',
        'reinvestment_rate' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function steps()
    {
        return $this->hasMany(BetPathStep::class);
    }
}
