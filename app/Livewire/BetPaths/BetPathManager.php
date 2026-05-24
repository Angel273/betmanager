<?php

namespace App\Livewire\BetPaths;

use App\Models\BetPath;
use App\Models\BetPathStep;
use App\Models\Bet;
use App\Models\Sport;
use App\Models\League;
use App\Models\Team;
use App\Models\BetSelection;
use App\Services\GeminiAnalysisService;
use Livewire\Component;
use Livewire\WithPagination;
use Exception;

class BetPathManager extends Component
{
    use WithPagination;

    public $activeSection = 'active'; // active, create, history

    // Form fields
    public $name = '';
    public $start_amount = '';
    public $target_amount = '';
    public $reinvestment_rate = 100.00;
    public $mode = 'steps'; // steps (calculate N) or odds (calculate O)
    public $avg_odds = '';
    public $num_steps = '';

    // Results preview
    public $previewSteps = [];
    public $calculatedOdds = 0.00;
    public $calculatedStepsCount = 0;

    // Suggestion properties
    public $showSuggestionModal = false;
    public $suggestedStepSport = '';
    public $suggestedStepOdds = 0.00;
    public $suggestedStepStake = 0.00;
    public $suggestedStepPathId = null;
    public $suggestedStepNumber = null;
    public $suggestedStepData = null;
    public $suggestingLoading = false;
    public $suggestingErrorMessage = '';

    public function mount()
    {
        abort_unless(auth()->check(), 403);
    }

    public function updated($propertyName)
    {
        // Run live calculations when relevant inputs change
        if (in_array($propertyName, ['start_amount', 'target_amount', 'reinvestment_rate', 'mode', 'avg_odds', 'num_steps'])) {
            $this->runPreviewCalculations();
        }
    }

    public function changeSection($section)
    {
        $this->activeSection = $section;
        $this->resetPage();
        if ($section === 'create') {
            $this->resetForm();
        }
    }

    private function resetForm()
    {
        $this->reset([
            'name', 'start_amount', 'target_amount', 'reinvestment_rate', 'mode', 'avg_odds', 'num_steps', 'previewSteps', 'calculatedOdds', 'calculatedStepsCount'
        ]);
        $this->reinvestment_rate = 100.00;
        $this->mode = 'steps';
        $this->resetErrorBag();
    }

    public function runPreviewCalculations()
    {
        $this->previewSteps = [];
        
        $S0 = floatval($this->start_amount);
        $T = floatval($this->target_amount);
        $R = floatval($this->reinvestment_rate);
        
        if ($S0 <= 0 || $T <= 0 || $T <= $S0 || $R < 0 || $R > 100) {
            return;
        }

        $r = $R / 100.0;
        $steps = [];
        $N = 0;
        $odds = 1.00;

        if ($this->mode === 'steps') {
            $odds = floatval($this->avg_odds);
            if ($odds <= 1.0) return;

            $g = 1 + $r * ($odds - 1);
            
            // Calculate N
            if ($T <= $S0 * $odds) {
                $N = 1;
            } else {
                $N = intval(ceil(1 + log($T / ($S0 * $odds)) / log($g)));
            }

            $this->calculatedStepsCount = $N;
            $this->calculatedOdds = $odds;
        } else {
            // mode === 'odds'
            $N = intval($this->num_steps);
            if ($N <= 0) return;

            // Solve for O numerically using binary search
            $low = 1.01;
            $high = 100.0;
            for ($i = 0; $i < 50; $i++) {
                $mid = ($low + $high) / 2;
                $g = 1 + $r * ($mid - 1);
                $val = $S0 * pow($g, $N - 1) * $mid;
                if ($val < $T) {
                    $low = $mid;
                } else {
                    $high = $mid;
                }
            }
            $odds = round($low, 2);
            $this->calculatedStepsCount = $N;
            $this->calculatedOdds = $odds;
        }

        // Build Steps list
        $currentStake = $S0;
        $g = 1 + $r * ($odds - 1);
        $totalPocketed = 0.00;

        for ($k = 1; $k <= $N; $k++) {
            $payout = round($currentStake * $odds, 2);
            $profit = $payout - $currentStake;
            $pocketed = round($profit * (1 - $r), 2);
            
            $steps[] = [
                'step_number' => $k,
                'stake' => $currentStake,
                'odds' => $odds,
                'payout' => $payout,
                'pocketed' => $pocketed,
            ];

            $totalPocketed += $pocketed;
            $currentStake = round($currentStake + ($profit * $r), 2);
        }

        $this->previewSteps = $steps;
    }

    public function createBetPath()
    {
        $this->validate([
            'name' => 'required|string|max:100',
            'start_amount' => 'required|numeric|min:0.01',
            'target_amount' => 'required|numeric|gt:start_amount',
            'reinvestment_rate' => 'required|numeric|between:0,100',
            'mode' => 'required|in:steps,odds',
            'avg_odds' => 'required_if:mode,steps|nullable|numeric|min:1.01',
            'num_steps' => 'required_if:mode,odds|nullable|integer|min:1',
        ], [
            'name.required' => 'El nombre del reto es requerido.',
            'start_amount.required' => 'El monto inicial es obligatorio.',
            'target_amount.required' => 'La meta es obligatoria y debe ser mayor al monto inicial.',
            'reinvestment_rate.between' => 'El porcentaje de reinversión debe estar entre 0% y 100%.',
            'avg_odds.required_if' => 'La cuota promedio es requerida en esta modalidad.',
            'num_steps.required_if' => 'El número de pasos es requerido en esta modalidad.',
        ]);

        $this->runPreviewCalculations();

        if (empty($this->previewSteps)) {
            session()->flash('error', 'No se pudieron calcular los pasos con los parámetros ingresados.');
            return;
        }

        \DB::transaction(function () {
            // Create Path
            $path = BetPath::create([
                'user_id' => auth()->id(),
                'name' => trim($this->name),
                'start_amount' => $this->start_amount,
                'target_amount' => $this->target_amount,
                'reinvestment_rate' => $this->reinvestment_rate,
                'current_step' => 1,
                'total_steps' => $this->calculatedStepsCount,
                'status' => 'active',
            ]);

            // Create Steps
            foreach ($this->previewSteps as $step) {
                BetPathStep::create([
                    'bet_path_id' => $path->id,
                    'step_number' => $step['step_number'],
                    'calculated_odds' => $step['odds'],
                    'expected_stake' => $step['stake'],
                    'expected_payout' => $step['payout'],
                    'bet_id' => null,
                    'status' => 'pending',
                ]);
            }
        });

        session()->flash('success', '¡Bet Path "' . $this->name . '" creado con éxito! Iniciando Paso 1.');
        $this->changeSection('active');
    }

    public function deleteBetPath($id)
    {
        $path = BetPath::where('user_id', auth()->id())->findOrFail($id);
        $path->delete();
        session()->flash('success', 'Bet Path eliminado con éxito.');
    }

    public function settleLinkedBet($betId, $status)
    {
        $bet = \App\Models\Bet::where('user_id', auth()->id())->findOrFail($betId);
        $bet->settle($status);
        session()->flash('success', 'Apuesta asociada calificada con éxito. El progreso del Bet Path ha sido actualizado.');
    }

    public function openSuggestionModal($pathId, $stepNumber, $targetOdds, $expectedStake)
    {
        $this->suggestedStepPathId = $pathId;
        $this->suggestedStepNumber = $stepNumber;
        $this->suggestedStepOdds = floatval($targetOdds);
        $this->suggestedStepStake = floatval($expectedStake);
        $this->suggestedStepSport = '';
        $this->suggestedStepData = null;
        $this->suggestingErrorMessage = '';
        $this->suggestingLoading = false;
        $this->showSuggestionModal = true;
    }

    public function getStepSuggestions()
    {
        $this->suggestingErrorMessage = '';
        $this->suggestedStepData = null;
        $this->suggestingLoading = true;

        try {
            $service = new GeminiAnalysisService();
            $this->suggestedStepData = $service->suggestBetPathStep(
                $this->suggestedStepOdds,
                $this->suggestedStepSport ?: null
            );

            if (empty($this->suggestedStepData) || empty($this->suggestedStepData['selections'])) {
                $this->suggestingErrorMessage = 'No se encontraron sugerencias de apuestas para la cuota especificada.';
            }
        } catch (Exception $e) {
            $this->suggestingErrorMessage = $e->getMessage();
            \Log::error('Error in BetPathManager getStepSuggestions: ' . $e->getMessage(), [
                'exception' => $e
            ]);
        } finally {
            $this->suggestingLoading = false;
        }
    }

    public function acceptStepSuggestion()
    {
        if (!$this->suggestedStepData || empty($this->suggestedStepData['selections'])) {
            return;
        }

        try {
            \DB::transaction(function () {
                $strategy = $this->suggestedStepData['strategy'] ?? 'single';
                
                // Calculate actual combined odds from selections
                $combinedOdds = 1.00;
                foreach ($this->suggestedStepData['selections'] as $sel) {
                    $combinedOdds *= floatval($sel['odds'] ?? 1.50);
                }
                $combinedOdds = round($combinedOdds, 2);

                // Create the Bet
                $bet = Bet::create([
                    'user_id' => auth()->id(),
                    'type' => $strategy,
                    'stake' => $this->suggestedStepStake,
                    'odds' => $combinedOdds,
                    'payout' => 0.00,
                    'profit' => 0.00,
                    'status' => 'pending',
                    'bet_path_id' => $this->suggestedStepPathId,
                    'bet_path_step' => $this->suggestedStepNumber,
                    'notes' => 'Registrada automáticamente a través del Asistente Bet Path con IA.',
                    'analyzed_at' => now(),
                    'ai_analysis' => [
                        'risk' => 'moderada', // default
                        'score' => $this->suggestedStepData['confidence_score'] ?? 80,
                        'analysis' => $this->suggestedStepData['analysis'] ?? 'Sugerencia de paso por Bet Path Finder.',
                        'h2h' => [],
                        'stats' => null
                    ]
                ]);

                // Create Selections
                foreach ($this->suggestedStepData['selections'] as $sel) {
                    // Resolve Sport
                    $sportName = trim($sel['sport'] ?? 'Otros');
                    $sport = Sport::firstOrCreate(['name' => $sportName]);

                    // Resolve Region and Country (Default)
                    $region = \App\Models\Region::firstOrCreate(['name' => 'Internacional']);
                    $country = \App\Models\Country::firstOrCreate([
                        'name' => 'Global',
                        'region_id' => $region->id
                    ]);

                    // Resolve League
                    $leagueName = trim($sel['league'] ?? 'General');
                    $league = League::firstOrCreate([
                        'name' => $leagueName,
                        'sport_id' => $sport->id
                    ], [
                        'country_id' => $country->id
                    ]);

                    // Resolve Teams
                    $teamHome = null;
                    if (!empty($sel['home_team']) && $sel['home_team'] !== 'N/A') {
                        $teamHome = Team::firstOrCreate([
                            'name' => trim($sel['home_team']),
                            'league_id' => $league->id
                        ]);
                    }

                    $teamAway = null;
                    if (!empty($sel['away_team']) && $sel['away_team'] !== 'N/A') {
                        $teamAway = Team::firstOrCreate([
                            'name' => trim($sel['away_team']),
                            'league_id' => $league->id
                        ]);
                    }

                    BetSelection::create([
                        'bet_id' => $bet->id,
                        'sport_id' => $sport->id,
                        'league_id' => $league->id,
                        'team_home_id' => $teamHome?->id,
                        'team_away_id' => $teamAway?->id,
                        'market_name' => trim($sel['market_name'] ?? 'Ganador'),
                        'selection' => trim($sel['selection'] ?? ($teamHome?->name ?? 'Apuesta')),
                        'odds' => floatval($sel['odds'] ?? 1.50),
                        'status' => 'pending',
                    ]);
                }

                // Link to Step
                $step = BetPathStep::where('bet_path_id', $this->suggestedStepPathId)
                    ->where('step_number', $this->suggestedStepNumber)
                    ->first();

                if ($step) {
                    $step->update([
                        'bet_id' => $bet->id,
                        'status' => 'pending'
                    ]);
                }
            });

            session()->flash('success', '¡Apuesta sugerida registrada y vinculada con éxito!');
            $this->showSuggestionModal = false;
            $this->suggestedStepData = null;

        } catch (Exception $e) {
            \Log::error('Error accepting Bet Path step suggestion: ' . $e->getMessage());
            $this->suggestingErrorMessage = 'Error al registrar y vincular la apuesta: ' . $e->getMessage();
        }
    }

    public function render()
    {
        $activePaths = BetPath::with(['steps.bet'])
            ->where('user_id', auth()->id())
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->get();

        $historyPaths = BetPath::where('user_id', auth()->id())
            ->whereIn('status', ['completed', 'failed'])
            ->orderBy('updated_at', 'desc')
            ->paginate(5);

        return view('livewire.bet-paths.bet-path-manager', [
            'activePaths' => $activePaths,
            'historyPaths' => $historyPaths,
        ]);
    }
}
