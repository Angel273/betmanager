<?php

namespace App\Livewire\Bets;

use App\Models\Bet;
use App\Models\BetSelection;
use App\Models\BetPath;
use App\Models\BetPathStep;
use App\Models\Sport;
use App\Models\League;
use App\Models\Team;
use App\Models\Player;
use Livewire\Component;

class BetRegistry extends Component
{
    public $type = 'single'; // single, parlay
    public $stake = '';
    public $notes = '';

    // Linked Bet Path properties
    public $bet_path_id = null;
    public $bet_path_step = null;
    public $betPathName = '';

    // Array of selections
    public $selections = [];

    // Dropdowns data cache
    public $sports = [];
    public $leagues = [];  // Format: [index => [League1, League2]]
    public $teams = [];    // Format: [index => [Team1, Team2]]
    public $players = [];  // Format: [index => [Player1, Player2]]

    public function mount()
    {
        // Load initial sports
        $this->sports = Sport::orderBy('name')->get();

        // Check query parameters for Bet Path link
        $this->bet_path_id = request()->query('bet_path_id');
        $this->bet_path_step = request()->query('step');
        $this->stake = request()->query('stake', '');

        if ($this->bet_path_id) {
            $path = BetPath::find($this->bet_path_id);
            if ($path) {
                $this->betPathName = $path->name . ' (Paso ' . $this->bet_path_step . ')';
            }
        }

        // Initialize with one selection
        $this->addSelection();
    }

    public function addSelection()
    {
        $this->selections[] = [
            'sport_id' => '',
            'league_id' => '',
            'team_home_id' => '',
            'team_away_id' => '',
            'player_id' => '',
            'market_name' => '',
            'selection' => '',
            'odds' => '',
        ];

        $index = count($this->selections) - 1;
        $this->leagues[$index] = [];
        $this->teams[$index] = [];
        $this->players[$index] = [];
    }

    public function removeSelection($index)
    {
        if (count($this->selections) > 1) {
            unset($this->selections[$index]);
            $this->selections = array_values($this->selections);
            
            // Re-index dropdowns cache
            unset($this->leagues[$index]);
            unset($this->teams[$index]);
            unset($this->players[$index]);
            $this->leagues = array_values($this->leagues);
            $this->teams = array_values($this->teams);
            $this->players = array_values($this->players);
        }
    }

    public function updatedType()
    {
        // If switched to single, keep only the first selection
        if ($this->type === 'single' && count($this->selections) > 1) {
            $this->selections = [array_shift($this->selections)];
            $this->leagues = [array_shift($this->leagues)];
            $this->teams = [array_shift($this->teams)];
            $this->players = [array_shift($this->players)];
        }
    }

    public function updatedSelections($value, $key)
    {
        // Parse the key to find the index and attribute
        // Example key: "0.sport_id"
        $parts = explode('.', $key);
        if (count($parts) === 2) {
            $index = intval($parts[0]);
            $attribute = $parts[1];

            if ($attribute === 'sport_id') {
                $sportId = $value;
                $this->selections[$index]['league_id'] = '';
                $this->selections[$index]['team_home_id'] = '';
                $this->selections[$index]['team_away_id'] = '';
                $this->selections[$index]['player_id'] = '';
                
                $this->leagues[$index] = League::where('sport_id', $sportId)->orderBy('name')->get();
                $this->teams[$index] = [];
                $this->players[$index] = [];
            } 
            elseif ($attribute === 'league_id') {
                $leagueId = $value;
                $this->selections[$index]['team_home_id'] = '';
                $this->selections[$index]['team_away_id'] = '';
                $this->selections[$index]['player_id'] = '';

                $this->teams[$index] = Team::where('league_id', $leagueId)->orderBy('name')->get();
                $this->players[$index] = [];
            }
            elseif ($attribute === 'team_home_id') {
                $teamId = $value;
                $this->selections[$index]['player_id'] = '';

                $this->players[$index] = Player::where('team_id', $teamId)->orderBy('name')->get();
            }
        }
    }

    public function getCalculatedOddsProperty()
    {
        $totalOdds = 1.00;
        $hasOdds = false;

        foreach ($this->selections as $sel) {
            if (!empty($sel['odds']) && is_numeric($sel['odds'])) {
                $totalOdds *= floatval($sel['odds']);
                $hasOdds = true;
            }
        }

        return $hasOdds ? round($totalOdds, 2) : 0.00;
    }

    public function getPotentialPayoutProperty()
    {
        $stake = floatval($this->stake);
        $odds = $this->calculatedOdds;

        return ($stake > 0 && $odds > 0) ? round($stake * $odds, 2) : 0.00;
    }

    public function saveBet()
    {
        // 1. Validation Rules
        $rules = [
            'type' => 'required|in:single,parlay',
            'stake' => 'required|numeric|min:0.01',
            'selections.*.sport_id' => 'required|exists:sports,id',
            'selections.*.league_id' => 'required|exists:leagues,id',
            'selections.*.market_name' => 'required|string|max:100',
            'selections.*.selection' => 'required|string|max:100',
            'selections.*.odds' => 'required|numeric|min:1.01',
        ];

        $messages = [
            'stake.required' => 'El monto apostado (stake) es obligatorio.',
            'stake.numeric' => 'El stake debe ser un número.',
            'stake.min' => 'El stake debe ser mayor a 0.',
            'selections.*.sport_id.required' => 'Debe seleccionar el deporte.',
            'selections.*.league_id.required' => 'Debe seleccionar la liga.',
            'selections.*.market_name.required' => 'Debe ingresar el mercado (Ej. Ambos Anotan, Hándicap).',
            'selections.*.selection.required' => 'Debe ingresar su selección (Ej. Sí, Lakers -3).',
            'selections.*.odds.required' => 'Debe ingresar la cuota.',
            'selections.*.odds.numeric' => 'La cuota debe ser un número.',
            'selections.*.odds.min' => 'La cuota mínima es 1.01.',
        ];

        $this->validate($rules, $messages);

        // 2. Database transaction to create the bet
        \DB::transaction(function () {
            $odds = $this->calculatedOdds;
            $payout = $this->potentialPayout;

            // Create Bet
            $bet = Bet::create([
                'user_id' => auth()->id(),
                'type' => $this->type,
                'stake' => $this->stake,
                'odds' => $odds,
                'payout' => 0.00, // Still pending
                'profit' => 0.00, // Still pending
                'status' => 'pending',
                'bet_path_id' => $this->bet_path_id,
                'bet_path_step' => $this->bet_path_step,
                'notes' => $this->notes,
            ]);

            // Create Selections
            foreach ($this->selections as $sel) {
                BetSelection::create([
                    'bet_id' => $bet->id,
                    'sport_id' => $sel['sport_id'],
                    'league_id' => $sel['league_id'],
                    'team_home_id' => $sel['team_home_id'] ?: null,
                    'team_away_id' => $sel['team_away_id'] ?: null,
                    'player_id' => $sel['player_id'] ?: null,
                    'market_name' => trim($sel['market_name']),
                    'selection' => trim($sel['selection']),
                    'odds' => $sel['odds'],
                    'status' => 'pending',
                ]);
            }

            // 3. Link back to Bet Path Step if applicable
            if ($this->bet_path_id && $this->bet_path_step) {
                $step = BetPathStep::where('bet_path_id', $this->bet_path_id)
                    ->where('step_number', $this->bet_path_step)
                    ->first();

                if ($step) {
                    $step->update([
                        'bet_id' => $bet->id,
                        'status' => 'pending',
                    ]);
                }
            }
        });

        session()->flash('success', 'Apuesta registrada con éxito.');
        
        if ($this->bet_path_id) {
            return redirect()->route('bet-paths');
        }

        return redirect()->route('dashboard');
    }

    public function render()
    {
        return view('livewire.bets.bet-registry');
    }
}
