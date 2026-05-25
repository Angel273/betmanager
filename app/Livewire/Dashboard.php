<?php

namespace App\Livewire;

use App\Models\Bet;
use App\Models\Sport;
use App\Services\GeminiAnalysisService;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;

class Dashboard extends Component
{
    use WithPagination;

    public $filterStatus = '';
    public $filterSport = '';
    public $showSettleModal = false;
    public $selectedBetId = null;
    public $customPayout = '';
    public $analyzingBetId = null;
    
    // AI Analysis Modal State
    public $showAiModal = false;
    public $selectedBetForAnalysis = null;

    public function analyzeBet($betId)
    {
        $this->analyzingBetId = $betId;
        $this->resetErrorBag();

        try {
            $bet = Bet::with('selections.sport', 'selections.league', 'selections.teamHome', 'selections.teamAway')->findOrFail($betId);
            $service = new GeminiAnalysisService();
            $analysis = $service->analyze($bet);

            $bet->update([
                'analyzed_at' => now(),
                'ai_analysis' => $analysis,
            ]);

            $this->selectedBetForAnalysis = $bet;
            $this->showAiModal = true;

            session()->flash('success', 'Apuesta #' . $betId . ' analizada con éxito por la IA.');
        } catch (\Exception $e) {
            \Log::error('Error en Dashboard al analizar la apuesta: ' . $e->getMessage(), [
                'exception' => $e,
                'bet_id' => $betId
            ]);
            session()->flash('error', 'Error en el análisis de IA: ' . $e->getMessage());
        } finally {
            $this->analyzingBetId = null;
        }
    }

    public function openAiAnalysisModal($betId)
    {
        $this->selectedBetForAnalysis = Bet::with(['selections.sport', 'selections.league', 'selections.teamHome', 'selections.teamAway'])
            ->where('user_id', auth()->id())
            ->findOrFail($betId);
        $this->showAiModal = true;
    }

    public function mount()
    {
        abort_unless(auth()->check(), 403);
    }

    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    public function updatedFilterSport()
    {
        $this->resetPage();
    }

    public function settleBet($betId, $status)
    {
        $bet = Bet::where('user_id', auth()->id())->findOrFail($betId);
        $bet->settle($status);
        session()->flash('success', 'La apuesta ha sido cobrada y las analíticas actualizadas.');
    }

    public function openCustomSettleModal($betId)
    {
        $bet = Bet::where('user_id', auth()->id())->findOrFail($betId);
        $this->selectedBetId = $bet->id;
        $this->customPayout = $bet->stake * $bet->odds;
        $this->showSettleModal = true;
    }

    public function settleCustomWon()
    {
        $this->validate([
            'customPayout' => 'required|numeric|min:0'
        ]);

        $bet = Bet::where('user_id', auth()->id())->findOrFail($this->selectedBetId);
        $bet->settle('won', $this->customPayout);

        $this->showSettleModal = false;
        $this->selectedBetId = null;
        session()->flash('success', 'Apuesta cobrada con un pago personalizado.');
    }

    public function deleteBet($betId)
    {
        $bet = Bet::where('user_id', auth()->id())->findOrFail($betId);
        $bet->delete();
        session()->flash('success', 'Apuesta eliminada del historial.');
    }

    /**
     * Calculate all profile stats for the user
     */
    private function getProfileStats()
    {
        $userId = auth()->id();
        
        $totalBets = Bet::where('user_id', $userId)->count();
        $settledBetsQuery = Bet::where('user_id', $userId)->whereIn('status', ['won', 'lost', 'voided', 'half_won', 'half_lost']);
        $settledCount = $settledBetsQuery->count();
        
        $wonCount = Bet::where('user_id', $userId)->whereIn('status', ['won', 'half_won'])->count();
        $lostCount = Bet::where('user_id', $userId)->whereIn('status', ['lost', 'half_lost'])->count();
        
        $totalStake = floatval(Bet::where('user_id', $userId)->sum('stake'));
        $totalPayout = floatval(Bet::where('user_id', $userId)->sum('payout'));
        
        $netProfit = $totalPayout - $totalStake;
        
        // Yield = (Net Profit / Total Stake) * 100
        $yield = $totalStake > 0 ? ($netProfit / $totalStake) * 100 : 0.00;
        
        // Win Rate = (Won Bets / Settled Bets) * 100
        // Half won counts as a win, half lost counts as a loss.
        $winRate = $settledCount > 0 ? ($wonCount / $settledCount) * 100 : 0.00;

        // Calculate Current Streak
        $recentBets = Bet::where('user_id', $userId)
            ->whereIn('status', ['won', 'lost'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
            
        $streakType = '';
        $streakCount = 0;
        
        foreach ($recentBets as $index => $b) {
            $status = $b->status;
            if ($index === 0) {
                $streakType = $status === 'won' ? 'W' : 'L';
                $streakCount = 1;
            } else {
                $currentType = $status === 'won' ? 'W' : 'L';
                if ($currentType === $streakType) {
                    $streakCount++;
                } else {
                    break;
                }
            }
        }

        $streak = $streakCount > 0 ? $streakCount . $streakType : 'N/A';

        return [
            'totalBets' => $totalBets,
            'settledCount' => $settledCount,
            'netProfit' => $netProfit,
            'totalStake' => $totalStake,
            'yield' => round($yield, 2),
            'winRate' => round($winRate, 2),
            'streak' => $streak,
            'wonCount' => $wonCount,
            'lostCount' => $lostCount
        ];
    }

    /**
     * Prepares data for profit evolution chart
     */
    public function getChartData()
    {
        $userId = auth()->id();
        $bets = Bet::where('user_id', $userId)
            ->whereIn('status', ['won', 'lost', 'voided', 'half_won', 'half_lost'])
            ->orderBy('created_at', 'asc')
            ->get();

        $series = [0.00];
        $categories = ['Inicio'];
        $cumulativeProfit = 0.00;

        foreach ($bets as $b) {
            $cumulativeProfit += floatval($b->profit);
            $series[] = round($cumulativeProfit, 2);
            $categories[] = $b->created_at->format('d M');
        }

        return [
            'series' => $series,
            'categories' => $categories
        ];
    }

    public function updateSelectionStatus($selectionId, $status)
    {
        $selection = \App\Models\BetSelection::with('bet')->findOrFail($selectionId);
        abort_unless($selection->bet->user_id === auth()->id(), 403);

        $selection->update(['status' => $status]);
        $selection->bet->recalculate();

        session()->flash('success', 'Estatus de selección actualizado.');
    }

    private function getAdvancedStats()
    {
        $userId = auth()->id();

        // 1. Markets win/loss stats
        $selections = \App\Models\BetSelection::whereHas('bet', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })
        ->whereIn('status', ['won', 'lost'])
        ->get();

        $marketStats = [];
        foreach ($selections as $sel) {
            $market = $sel->market_name ?: 'Desconocido';
            if (!isset($marketStats[$market])) {
                $marketStats[$market] = ['total' => 0, 'won' => 0, 'lost' => 0];
            }
            $marketStats[$market]['total']++;
            if ($sel->status === 'won') {
                $marketStats[$market]['won']++;
            } else {
                $marketStats[$market]['lost']++;
            }
        }

        foreach ($marketStats as $market => &$mStats) {
            $mStats['win_rate'] = $mStats['total'] > 0 ? round(($mStats['won'] / $mStats['total']) * 100, 1) : 0;
        }
        unset($mStats);

        uasort($marketStats, function ($a, $b) {
            if ($b['win_rate'] === $a['win_rate']) {
                return $b['total'] <=> $a['total'];
            }
            return $b['win_rate'] <=> $a['win_rate'];
        });

        $marketStats = array_slice($marketStats, 0, 5, true);

        // 2. Profitability by Sport
        $settledBets = Bet::where('user_id', $userId)
            ->whereIn('status', ['won', 'lost', 'voided', 'half_won', 'half_lost'])
            ->with('selections.sport')
            ->get();

        $sportProfit = [];
        foreach ($settledBets as $bet) {
            $selectionsCount = $bet->selections->count();
            if ($selectionsCount === 0) continue;
            
            $profitPerSelection = floatval($bet->profit) / $selectionsCount;
            foreach ($bet->selections as $sel) {
                $sportName = $sel->sport->name ?? 'Otros';
                if (!isset($sportProfit[$sportName])) {
                    $sportProfit[$sportName] = 0.00;
                }
                $sportProfit[$sportName] += $profitPerSelection;
            }
        }
        arsort($sportProfit);

        // 3. Efficiency by Bet Type (Single vs Parlay)
        $betTypeStats = [];
        foreach (['single', 'parlay'] as $type) {
            $typeQuery = Bet::where('user_id', $userId)->where('type', $type);
            $total = $typeQuery->count();
            $settledQuery = (clone $typeQuery)->whereIn('status', ['won', 'lost', 'voided', 'half_won', 'half_lost']);
            $settled = $settledQuery->count();
            $won = (clone $typeQuery)->whereIn('status', ['won', 'half_won'])->count();
            $profit = floatval($typeQuery->sum('profit'));
            
            $winRate = $settled > 0 ? ($won / $settled) * 100 : 0.00;
            
            $betTypeStats[$type] = [
                'total' => $total,
                'win_rate' => round($winRate, 1),
                'profit' => $profit
            ];
        }

        return [
            'markets' => $marketStats,
            'sports' => $sportProfit,
            'types' => $betTypeStats
        ];
    }

    public function render()
    {
        $userId = auth()->id();
        $sportsList = Sport::orderBy('name')->get();

        // Build main bets query
        $query = Bet::with(['selections.sport', 'selections.league', 'selections.teamHome', 'selections.teamAway'])
            ->where('user_id', $userId);

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterSport) {
            $query->whereHas('selections', function ($q) {
                $q->where('sport_id', $this->filterSport);
            });
        }

        $bets = $query->orderBy('created_at', 'desc')->paginate(8);

        return view('livewire.dashboard', [
            'stats' => $this->getProfileStats(),
            'bets' => $bets,
            'sportsList' => $sportsList,
            'chartData' => $this->getChartData(),
            'advancedStats' => $this->getAdvancedStats()
        ]);
    }
}
