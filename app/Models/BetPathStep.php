<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BetPathStep extends Model
{
    protected $fillable = [
        'bet_path_id',
        'step_number',
        'calculated_odds',
        'expected_stake',
        'expected_payout',
        'bet_id',
        'status'
    ];

    protected $casts = [
        'calculated_odds' => 'decimal:2',
        'expected_stake' => 'decimal:2',
        'expected_payout' => 'decimal:2',
    ];

    public function betPath()
    {
        return $this->belongsTo(BetPath::class);
    }

    public function bet()
    {
        return $this->belongsTo(Bet::class);
    }
}
