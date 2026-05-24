<?php

namespace App\Livewire;

use App\Models\Bet;
use App\Models\Sport;
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

    #[On('analyzeBet')]
    public function setAnalyzingBet($betId)
    {
        $this->analyzingBetId = $betId;
    }

    #[On('refreshDashboard')]
    public function refresh()
    {
        $this->analyzingBetId = null;
        // Re-renders the component when AI analysis finishes
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
            'chartData' => $this->getChartData()
        ]);
    }
}
