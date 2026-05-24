<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BetSelection extends Model
{
    protected $fillable = [
        'bet_id',
        'sport_id',
        'league_id',
        'team_home_id',
        'team_away_id',
        'player_id',
        'market_name',
        'selection',
        'odds',
        'status'
    ];

    protected $casts = [
        'odds' => 'decimal:2'
    ];

    public function bet()
    {
        return $this->belongsTo(Bet::class);
    }

    public function sport()
    {
        return $this->belongsTo(Sport::class);
    }

    public function league()
    {
        return $this->belongsTo(League::class);
    }

    public function teamHome()
    {
        return $this->belongsTo(Team::class, 'team_home_id');
    }

    public function teamAway()
    {
        return $this->belongsTo(Team::class, 'team_away_id');
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}
