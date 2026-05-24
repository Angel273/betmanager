<?php

namespace App\Livewire;

use App\Models\Bet;
use App\Services\GeminiAnalysisService;
use Livewire\Component;
use Livewire\Attributes\On;
use Exception;

class BetAnalyzer extends Component
{
    public $analyzingId = null;
    public $errorMessage = '';

    #[On('analyzeBet')]
    public function analyzeBet($betId)
    {
        $this->analyzingId = $betId;
        $this->errorMessage = '';

        try {
            $bet = Bet::with('selections.sport', 'selections.league', 'selections.teamHome', 'selections.teamAway')->findOrFail($betId);
            $service = new GeminiAnalysisService();
            $analysis = $service->analyze($bet);

            $bet->update([
                'analyzed_at' => now(),
                'ai_analysis' => $analysis,
            ]);

            session()->flash('success', 'Apuesta #' . $betId . ' analizada con éxito por la IA.');
            
            // Dispatch event to parent component to reload the bets list
            $this->dispatch('refreshDashboard');
            $this->dispatch('refreshBetPaths'); // Just in case we are on the bet path page
        } catch (Exception $e) {
            \Log::error('Error en BetAnalyzer al analizar la apuesta: ' . $e->getMessage(), [
                'exception' => $e,
                'bet_id' => $betId
            ]);
            $this->errorMessage = $e->getMessage();
            session()->flash('error', 'Error en el análisis de IA: ' . $e->getMessage());
            
            // Dispatch refresh event on failure too, to clear loaders in parent views
            $this->dispatch('refreshDashboard');
            $this->dispatch('refreshBetPaths');
        } finally {
            $this->analyzingId = null;
        }
    }

    public function render()
    {
        return view('livewire.bet-analyzer');
    }
}
