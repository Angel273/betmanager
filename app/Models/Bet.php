<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bet extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'stake',
        'odds',
        'payout',
        'profit',
        'status',
        'bet_path_id',
        'bet_path_step',
        'notes',
        'analyzed_at',
        'ai_analysis'
    ];

    protected $casts = [
        'analyzed_at' => 'datetime',
        'ai_analysis' => 'array',
        'stake' => 'decimal:2',
        'odds' => 'decimal:2',
        'payout' => 'decimal:2',
        'profit' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function selections()
    {
        return $this->hasMany(BetSelection::class);
    }

    public function betPath()
    {
        return $this->belongsTo(BetPath::class);
    }

    public function betPathStep()
    {
        return $this->hasOne(BetPathStep::class);
    }

    /**
     * Settle the bet and update profit/payout, selections, and any linked Bet Path.
     */
    public function settle(string $status, $actualPayout = null)
    {
        $this->status = $status;
        $odds = floatval($this->odds);
        $stake = floatval($this->stake);

        if ($status === 'won') {
            $this->payout = $actualPayout !== null ? floatval($actualPayout) : round($stake * $odds, 2);
            $this->profit = $this->payout - $stake;
        } elseif ($status === 'lost') {
            $this->payout = 0.00;
            $this->profit = -$stake;
        } elseif ($status === 'voided') {
            $this->payout = $stake; // return stake
            $this->profit = 0.00;
        } elseif ($status === 'half_won') {
            // Half won: half of the stake is voided, half wins.
            $this->payout = round(($stake / 2) + (($stake / 2) * $odds), 2);
            $this->profit = $this->payout - $stake;
        } elseif ($status === 'half_lost') {
            // Half lost: half is lost, half is voided.
            $this->payout = round($stake / 2, 2);
            $this->profit = $this->payout - $stake;
        }

        $this->save();

        // Update selections to match
        foreach ($this->selections as $sel) {
            if ($sel->status === 'pending') {
                $sel->update([
                    'status' => $status === 'won' ? 'won' : ($status === 'lost' ? 'lost' : 'voided')
                ]);
            }
        }

        // Handle Bet Path progression
        if ($this->bet_path_id && $this->bet_path_step) {
            $path = $this->betPath;
            $step = BetPathStep::where('bet_path_id', $this->bet_path_id)
                ->where('step_number', $this->bet_path_step)
                ->first();

            if ($step && $path && $path->status === 'active') {
                // Determine if this step reached its expected payout
                $expectedPayout = floatval($step->expected_payout);
                $isStepSuccess = ($status === 'won' && $this->payout >= $expectedPayout);

                if ($isStepSuccess) {
                    $step->update(['status' => 'won']);

                    // If it is the last step of the path
                    if ($this->bet_path_step == $path->total_steps) {
                        $path->update([
                            'status' => 'completed',
                            'current_amount' => $this->payout
                        ]);
                    } else {
                        // Move to next step
                        $nextStepNumber = $this->bet_path_step + 1;
                        $reinvestmentRate = floatval($path->reinvestment_rate) / 100;
                        
                        // Calculate next stake:
                        // Payout of current step * reinvestment rate
                        $nextStake = round($this->payout * $reinvestmentRate, 2);

                        $nextStep = BetPathStep::where('bet_path_id', $this->bet_path_id)
                            ->where('step_number', $nextStepNumber)
                            ->first();

                        if ($nextStep) {
                            $nextStep->update([
                                'expected_stake' => $nextStake,
                                'expected_payout' => round($nextStake * floatval($nextStep->calculated_odds), 2)
                            ]);
                        }

                        $path->update([
                            'current_step' => $nextStepNumber,
                            'current_amount' => $this->payout
                        ]);
                    }
                } else {
                    // Settle didn't reach expected payout -> Path failed!
                    $step->update(['status' => 'lost']);
                    $path->update([
                        'status' => 'failed',
                        'current_amount' => $this->payout
                    ]);
                }
            }
        }
    }
}
