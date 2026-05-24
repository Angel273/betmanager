<?php

namespace App\Livewire\BetPaths;

use App\Models\BetPath;
use App\Models\BetPathStep;
use Livewire\Component;
use Livewire\WithPagination;

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
