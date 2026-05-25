<?php

namespace App\Livewire\Bets;

use App\Models\Bet;
use App\Models\BetSelection;
use App\Models\Sport;
use App\Models\League;
use App\Models\Team;
use Livewire\Component;
use App\Services\GeminiAnalysisService;
use Exception;

class BetFinder extends Component
{
    public $sport = '';
    public $risk = 'segura';
    public $minOdds = '';
    public $maxOdds = '';
    public $searchDate = '';      // specific date to look for matches
    public $resultCount = 3;      // how many bets to suggest

    public $suggestions = [];
    public $loading = false;
    public $errorMessage = '';
    
    // Registration modal state
    public $showRegisterModal = false;
    public $selectedSuggestion = null;
    public $stake = 100.00;

    public function mount()
    {
        abort_unless(auth()->check(), 403);
        $this->searchDate = today()->format('Y-m-d');
    }

    public function search()
    {
        $this->errorMessage = '';
        $this->suggestions = [];
        $this->loading = true;

        $this->validate([
            'risk'          => 'required|in:segura,moderada,improbable',
            'minOdds'       => 'nullable|numeric|min:1.01',
            'maxOdds'       => 'nullable|numeric|gte:minOdds',
            'searchDate'    => 'required|date|after_or_equal:today',
            'resultCount'   => 'required|integer|min:1|max:10',
        ], [
            'risk.required'          => 'El nivel de riesgo es obligatorio.',
            'minOdds.numeric'        => 'La cuota mínima debe ser un número.',
            'minOdds.min'            => 'La cuota mínima debe ser al menos 1.01.',
            'maxOdds.numeric'        => 'La cuota máxima debe ser un número.',
            'maxOdds.gte'            => 'La cuota máxima debe ser mayor o igual a la cuota mínima.',
            'searchDate.required'    => 'La fecha de búsqueda es obligatoria.',
            'searchDate.date'        => 'La fecha de búsqueda debe ser una fecha válida.',
            'searchDate.after_or_equal' => 'La fecha de búsqueda debe ser hoy o posterior.',
            'resultCount.min'        => 'Mínimo 1 apuesta.',
            'resultCount.max'        => 'Máximo 10 apuestas.',
        ]);

        try {
            $service = new GeminiAnalysisService();
            $this->suggestions = $service->suggestBets(
                $this->sport ?: null,
                $this->risk,
                $this->minOdds ? floatval($this->minOdds) : null,
                $this->maxOdds ? floatval($this->maxOdds) : null,
                $this->searchDate,
                intval($this->resultCount)
            );
            
            if (empty($this->suggestions)) {
                $this->errorMessage = 'No se encontraron apuestas que coincidan con los filtros en este momento.';
            }
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
            \Log::error('Error in BetFinder search: ' . $e->getMessage(), [
                'exception' => $e
            ]);
        } finally {
            $this->loading = false;
        }
    }

    public function openRegisterModal($index)
    {
        if (isset($this->suggestions[$index])) {
            $this->selectedSuggestion = $this->suggestions[$index];
            $this->stake = 100.00;
            $this->showRegisterModal = true;
        }
    }

    public function closeRegisterModal()
    {
        $this->showRegisterModal = false;
        $this->selectedSuggestion = null;
    }

    public function registerBet()
    {
        $this->validate([
            'stake' => 'required|numeric|min:0.01'
        ], [
            'stake.required' => 'El monto es obligatorio.',
            'stake.numeric' => 'El monto debe ser un número.',
            'stake.min' => 'El monto debe ser mayor a 0.',
        ]);

        if (!$this->selectedSuggestion) {
            return;
        }

        try {
            \DB::transaction(function () {
                $rec = $this->selectedSuggestion;

                // 1. Resolve Sport
                $sportName = trim($rec['sport'] ?? 'Otros');
                $sport = Sport::firstOrCreate(['name' => $sportName]);

                // 2. Resolve Region and Country (Default)
                $region = \App\Models\Region::firstOrCreate(['name' => 'Internacional']);
                $country = \App\Models\Country::firstOrCreate([
                    'name' => 'Global',
                    'region_id' => $region->id
                ]);

                // 3. Resolve League
                $leagueName = trim($rec['league'] ?? 'General');
                $league = League::firstOrCreate([
                    'name' => $leagueName,
                    'sport_id' => $sport->id
                ], [
                    'country_id' => $country->id
                ]);

                // 4. Resolve Teams
                $teamHome = null;
                if (!empty($rec['home_team']) && $rec['home_team'] !== 'N/A') {
                    $teamHome = Team::firstOrCreate([
                        'name' => trim($rec['home_team']),
                        'league_id' => $league->id
                    ]);
                }

                $teamAway = null;
                if (!empty($rec['away_team']) && $rec['away_team'] !== 'N/A') {
                    $teamAway = Team::firstOrCreate([
                        'name' => trim($rec['away_team']),
                        'league_id' => $league->id
                    ]);
                }

                // 5. Create Bet
                $odds = floatval($rec['odds'] ?? 1.50);
                $bet = Bet::create([
                    'user_id' => auth()->id(),
                    'type' => 'single',
                    'stake' => $this->stake,
                    'odds' => $odds,
                    'payout' => 0.00,
                    'profit' => 0.00,
                    'status' => 'pending',
                    'notes' => 'Registrada automáticamente desde Bet Finder con IA.',
                    'analyzed_at' => now(),
                    'ai_analysis' => [
                        'risk' => strtolower($rec['risk'] ?? $this->risk),
                        'score' => $rec['confidence_score'] ?? 80,
                        'analysis' => $rec['analysis'] ?? 'Sugerido por Bet Finder.',
                        'h2h' => [],
                        'stats' => null
                    ]
                ]);

                // 6. Create Bet Selection
                BetSelection::create([
                    'bet_id' => $bet->id,
                    'sport_id' => $sport->id,
                    'league_id' => $league->id,
                    'team_home_id' => $teamHome?->id,
                    'team_away_id' => $teamAway?->id,
                    'market_name' => trim($rec['market_name'] ?? 'Ganador'),
                    'selection' => trim($rec['selection'] ?? ($teamHome?->name ?? 'Apuesta')),
                    'odds' => $odds,
                    'status' => 'pending',
                ]);
            });

            session()->flash('success', '¡Apuesta registrada con éxito en el Dashboard!');
            $this->closeRegisterModal();
            return redirect()->route('dashboard');

        } catch (Exception $e) {
            \Log::error('Error registering bet from suggestion: ' . $e->getMessage());
            $this->errorMessage = 'Error al registrar la apuesta: ' . $e->getMessage();
            $this->closeRegisterModal();
        }
    }

    public function render()
    {
        $sportsList = Sport::orderBy('name')->get();

        return view('livewire.bets.bet-finder', [
            'sportsList' => $sportsList
        ]);
    }
}
