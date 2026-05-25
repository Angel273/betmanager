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
use App\Models\Country;
use App\Models\Region;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\GeminiAnalysisService;

class BetRegistry extends Component
{
    use WithFileUploads;

    public $ticketImage;
    public $type = 'single'; // single, parlay
    public $stake = '';
    public $notes = '';

    // Quick team creation properties
    public $showQuickTeamModal = false;
    public $quickTeamName = '';
    public $quickTeamLeagueId = null;
    public $quickTeamSelectionIndex = null;
    public $quickTeamField = '';

    // Missing entities fallback properties
    public $showMissingEntitiesModal = false;
    public $missingEntities = [];
    public $allCountries = [];
    public $tempSelections = [];

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
        // Load all countries
        $this->allCountries = Country::orderBy('name')->get();

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

    public function openQuickTeamModal($index, $field)
    {
        abort_unless(auth()->user()->is_admin, 403);

        $leagueId = $this->selections[$index]['league_id'] ?? null;
        if (!$leagueId) {
            session()->flash('error', 'Por favor selecciona primero una liga antes de agregar un equipo.');
            return;
        }

        $this->quickTeamLeagueId = $leagueId;
        $this->quickTeamSelectionIndex = $index;
        $this->quickTeamField = $field;
        $this->quickTeamName = '';
        $this->showQuickTeamModal = true;
    }

    public function saveQuickTeam()
    {
        abort_unless(auth()->user()->is_admin, 403);

        $this->validate([
            'quickTeamName' => 'required|string|max:100',
        ], [
            'quickTeamName.required' => 'El nombre del equipo es obligatorio.',
        ]);

        // Double check that it doesn't already exist in this league to avoid duplicates
        $existingTeam = Team::where('league_id', $this->quickTeamLeagueId)
            ->where('name', 'like', trim($this->quickTeamName))
            ->first();

        if ($existingTeam) {
            $team = $existingTeam;
        } else {
            $team = Team::create([
                'name' => trim($this->quickTeamName),
                'league_id' => $this->quickTeamLeagueId,
            ]);
        }

        // Refresh dropdown cache for this selection index
        $this->teams[$this->quickTeamSelectionIndex] = Team::where('league_id', $this->quickTeamLeagueId)
            ->orderBy('name')
            ->get();

        // Auto-select the team ID
        $this->selections[$this->quickTeamSelectionIndex][$this->quickTeamField] = $team->id;

        $this->showQuickTeamModal = false;
        session()->flash('success', 'Equipo "' . $team->name . '" creado y seleccionado con éxito.');
    }

    public function updatedTicketImage()
    {
        $this->validate([
            'ticketImage' => 'image|max:4096', // Max 4MB
        ]);

        try {
            $service = new GeminiAnalysisService();
            $parsedData = $service->parseTicketImage($this->ticketImage->getRealPath());

            // 1. Set global ticket properties if extracted
            if (isset($parsedData['stake']) && is_numeric($parsedData['stake'])) {
                $this->stake = floatval($parsedData['stake']);
            }
            if (isset($parsedData['type']) && in_array($parsedData['type'], ['single', 'parlay'])) {
                $this->type = $parsedData['type'];
            }

            // 2. Clear current selections and populate with parsed ones
            $this->tempSelections = [];
            $this->selections = [];
            $this->leagues = [];
            $this->teams = [];
            $this->players = [];

            $missingEntities = [];

            if (isset($parsedData['selections']) && is_array($parsedData['selections'])) {
                foreach ($parsedData['selections'] as $index => $parsedSel) {
                    $sport = null;
                    if (!empty($parsedSel['sport'])) {
                        $sport = Sport::where('name', 'like', '%' . $parsedSel['sport'] . '%')->first();
                    }
                    if (!$sport) {
                        $sport = Sport::where('name', 'Fútbol')->first() ?? Sport::first();
                    }
                    $sportId = $sport->id;

                    // Match League
                    $league = null;
                    if (!empty($parsedSel['league'])) {
                        $league = League::where('sport_id', $sportId)
                            ->whereRaw('LOWER(name) = ?', [strtolower(trim($parsedSel['league']))])
                            ->first();
                        if (!$league) {
                            $league = League::where('sport_id', $sportId)
                                ->where('name', 'like', '%' . trim($parsedSel['league']) . '%')
                                ->first();
                        }
                    }

                    // Match Teams
                    $homeTeam = null;
                    if (!empty($parsedSel['home_team'])) {
                        if ($league) {
                            $homeTeam = Team::where('league_id', $league->id)
                                ->whereRaw('LOWER(name) = ?', [strtolower(trim($parsedSel['home_team']))])
                                ->first();
                            if (!$homeTeam) {
                                $homeTeam = Team::where('league_id', $league->id)
                                    ->where('name', 'like', '%' . trim($parsedSel['home_team']) . '%')
                                    ->first();
                            }
                        } else {
                            $homeTeam = Team::whereHas('league', function ($q) use ($sportId) {
                                $q->where('sport_id', $sportId);
                            })
                            ->whereRaw('LOWER(name) = ?', [strtolower(trim($parsedSel['home_team']))])
                            ->first();
                        }
                    }

                    $awayTeam = null;
                    if (!empty($parsedSel['away_team'])) {
                        if ($league) {
                            $awayTeam = Team::where('league_id', $league->id)
                                ->whereRaw('LOWER(name) = ?', [strtolower(trim($parsedSel['away_team']))])
                                ->first();
                            if (!$awayTeam) {
                                $awayTeam = Team::where('league_id', $league->id)
                                    ->where('name', 'like', '%' . trim($parsedSel['away_team']) . '%')
                                    ->first();
                            }
                        } else {
                            $awayTeam = Team::whereHas('league', function ($q) use ($sportId) {
                                $q->where('sport_id', $sportId);
                            })
                            ->whereRaw('LOWER(name) = ?', [strtolower(trim($parsedSel['away_team']))])
                            ->first();
                        }
                    }

                    // Fallback matching league if team is found
                    if ($homeTeam && !$league) {
                        $league = $homeTeam->league;
                    }
                    if ($awayTeam && !$league) {
                        $league = $awayTeam->league;
                    }

                    // Re-check teams if league was newly resolved here!
                    if ($league) {
                        if (!$homeTeam && !empty($parsedSel['home_team'])) {
                            $homeTeam = Team::where('league_id', $league->id)
                                ->where('name', 'like', '%' . trim($parsedSel['home_team']) . '%')
                                ->first();
                        }
                        if (!$awayTeam && !empty($parsedSel['away_team'])) {
                            $awayTeam = Team::where('league_id', $league->id)
                                ->where('name', 'like', '%' . trim($parsedSel['away_team']) . '%')
                                ->first();
                        }
                    }

                    $leagueId = $league ? $league->id : null;
                    $teamHomeId = $homeTeam ? $homeTeam->id : null;
                    $teamAwayId = $awayTeam ? $awayTeam->id : null;

                    // If league or teams are missing
                    $isLeagueMissing = !$league;
                    $isHomeTeamMissing = !$homeTeam && !empty($parsedSel['home_team']);
                    $isAwayTeamMissing = !$awayTeam && !empty($parsedSel['away_team']);

                    if ($isLeagueMissing || $isHomeTeamMissing || $isAwayTeamMissing) {
                        // Match country if league is missing
                        $matchedCountryId = '';
                        $parsedCountryName = $parsedSel['league_country'] ?? '';
                        if (!empty($parsedCountryName)) {
                            $matchedCountry = Country::whereRaw('LOWER(name) = ?', [strtolower(trim($parsedCountryName))])->first();
                            if (!$matchedCountry) {
                                $matchedCountry = Country::where('name', 'like', '%' . trim($parsedCountryName) . '%')->first();
                            }
                            if ($matchedCountry) {
                                $matchedCountryId = $matchedCountry->id;
                            }
                        }

                        $missingEntities[] = [
                            'index' => $index,
                            'sport_id' => $sportId,
                            'sport_name' => $sport->name,
                            
                            'league_exists' => !$isLeagueMissing,
                            'league_id' => $leagueId,
                            'league_name' => $isLeagueMissing ? ($parsedSel['league'] ?? '') : ($league ? $league->name : ''),
                            
                            'country_id' => $matchedCountryId,
                            'country_name' => $parsedCountryName,
                            'new_country_name' => '',
                            
                            'home_team_exists' => !$isHomeTeamMissing,
                            'home_team_id' => $teamHomeId,
                            'home_team_name' => $isHomeTeamMissing ? ($parsedSel['home_team'] ?? '') : ($homeTeam ? $homeTeam->name : ''),
                            
                            'away_team_exists' => !$isAwayTeamMissing,
                            'away_team_id' => $teamAwayId,
                            'away_team_name' => $isAwayTeamMissing ? ($parsedSel['away_team'] ?? '') : ($awayTeam ? $awayTeam->name : ''),

                            'market_name' => $parsedSel['market_name'] ?? '',
                            'selection' => $parsedSel['selection'] ?? '',
                            'odds' => $parsedSel['odds'] ?? '',
                        ];
                    }

                    $this->tempSelections[] = [
                        'sport_id' => $sportId,
                        'league_id' => $leagueId ?? '',
                        'team_home_id' => $teamHomeId ?? '',
                        'team_away_id' => $teamAwayId ?? '',
                        'player_id' => '',
                        'market_name' => $parsedSel['market_name'] ?? '',
                        'selection' => $parsedSel['selection'] ?? '',
                        'odds' => $parsedSel['odds'] ?? '',
                    ];
                }
            }

            if (empty($missingEntities)) {
                $this->selections = $this->tempSelections;
                
                // Load select lists for each selection
                foreach ($this->selections as $index => $sel) {
                    $sportId = $sel['sport_id'];
                    $leagueId = $sel['league_id'];
                    
                    if ($sportId) {
                        $this->leagues[$index] = League::where('sport_id', $sportId)->orderBy('name')->get();
                    } else {
                        $this->leagues[$index] = [];
                    }
                    if ($leagueId) {
                        $this->teams[$index] = Team::where('league_id', $leagueId)->orderBy('name')->get();
                    } else {
                        $this->teams[$index] = [];
                    }
                    $this->players[$index] = [];
                }
                
                session()->flash('success', 'Ticket de apuestas escaneado y procesado con éxito por Gemini. Por favor revisa los datos antes de guardar.');
            } else {
                $this->missingEntities = $missingEntities;
                $this->showMissingEntitiesModal = true;
            }

        } catch (\Exception $e) {
            \Log::error('Error en updatedTicketImage del Livewire BetRegistry: ' . $e->getMessage(), [
                'exception' => $e,
                'file_name' => $this->ticketImage ? $this->ticketImage->getClientOriginalName() : 'N/A'
            ]);
            session()->flash('error', 'Error al escanear el ticket: ' . $e->getMessage());
        } finally {
            $this->ticketImage = null; // Reset file input
        }
    }

    public function saveMissingEntities()
    {
        // 1. Validation
        foreach ($this->missingEntities as $idx => $missing) {
            if (!$missing['league_exists']) {
                if (empty(trim($missing['league_name']))) {
                    $this->addError("missingEntities.{$idx}.league_name", 'El nombre de la liga es obligatorio.');
                    return;
                }
                if ($missing['country_id'] === 'new' && empty(trim($missing['new_country_name']))) {
                    $this->addError("missingEntities.{$idx}.new_country_name", 'El nombre del país es obligatorio.');
                    return;
                }
            }
            if (!$missing['home_team_exists'] && !empty($missing['home_team_name']) && empty(trim($missing['home_team_name']))) {
                $this->addError("missingEntities.{$idx}.home_team_name", 'El nombre del equipo local es obligatorio.');
                return;
            }
            if (!$missing['away_team_exists'] && !empty($missing['away_team_name']) && empty(trim($missing['away_team_name']))) {
                $this->addError("missingEntities.{$idx}.away_team_name", 'El nombre del equipo visitante es obligatorio.');
                return;
            }
        }

        \DB::transaction(function () {
            foreach ($this->missingEntities as $idx => &$missing) {
                $sportId = $missing['sport_id'];
                $leagueId = $missing['league_id'];

                // Create Country if new
                $countryId = $missing['country_id'];
                if ($countryId === 'new') {
                    $region = Region::where('name', 'Internacional')->first() ?? Region::first();
                    if (!$region) {
                        $region = Region::create(['name' => 'Internacional']);
                    }
                    $country = Country::create([
                        'name' => trim($missing['new_country_name']),
                        'region_id' => $region->id
                    ]);
                    $countryId = $country->id;
                } elseif (empty($countryId)) {
                    $countryId = null;
                }

                // Create League if not exists
                if (!$missing['league_exists']) {
                    $league = League::create([
                        'name' => trim($missing['league_name']),
                        'sport_id' => $sportId,
                        'country_id' => $countryId,
                    ]);
                    $leagueId = $league->id;
                    $missing['league_id'] = $leagueId;
                    $missing['league_exists'] = true;
                }

                // Create Home Team if not exists and we have a name
                $homeTeamId = $missing['home_team_id'];
                if (!$missing['home_team_exists'] && !empty(trim($missing['home_team_name']))) {
                    $team = Team::create([
                        'name' => trim($missing['home_team_name']),
                        'league_id' => $leagueId
                    ]);
                    $homeTeamId = $team->id;
                    $missing['home_team_id'] = $homeTeamId;
                    $missing['home_team_exists'] = true;
                }

                // Create Away Team if not exists and we have a name
                $awayTeamId = $missing['away_team_id'];
                if (!$missing['away_team_exists'] && !empty(trim($missing['away_team_name']))) {
                    $team = Team::create([
                        'name' => trim($missing['away_team_name']),
                        'league_id' => $leagueId
                    ]);
                    $awayTeamId = $team->id;
                    $missing['away_team_id'] = $awayTeamId;
                    $missing['away_team_exists'] = true;
                }

                // Update tempSelections with newly created IDs
                $selIndex = $missing['index'];
                $this->tempSelections[$selIndex]['league_id'] = $leagueId;
                $this->tempSelections[$selIndex]['team_home_id'] = $homeTeamId ?? '';
                $this->tempSelections[$selIndex]['team_away_id'] = $awayTeamId ?? '';
            }
        });

        // 2. Set selections to tempSelections
        $this->selections = $this->tempSelections;

        // 3. Load select lists caches
        foreach ($this->selections as $index => $sel) {
            $sportId = $sel['sport_id'];
            $leagueId = $sel['league_id'];

            if ($sportId) {
                $this->leagues[$index] = League::where('sport_id', $sportId)->orderBy('name')->get();
            } else {
                $this->leagues[$index] = [];
            }

            if ($leagueId) {
                $this->teams[$index] = Team::where('league_id', $leagueId)->orderBy('name')->get();
            } else {
                $this->teams[$index] = [];
            }
            $this->players[$index] = [];
        }

        // 4. Refresh all countries in case a new country was added
        $this->allCountries = Country::orderBy('name')->get();

        // 5. Close modal & cleanup
        $this->showMissingEntitiesModal = false;
        $this->missingEntities = [];
        $this->tempSelections = [];

        session()->flash('success', 'Entidades creadas en base de datos y pre-seleccionadas correctamente.');
    }

    public function render()
    {
        return view('livewire.bets.bet-registry');
    }
}
