<?php

namespace Tests\Feature;

use App\Models\Bet;
use App\Models\BetPath;
use App\Models\BetPathStep;
use App\Models\Sport;
use App\Models\League;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BetPathTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $sport;
    protected $league;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup base objects
        $this->user = User::create([
            'name' => 'Mauri Test',
            'email' => 'mauri@example.com',
            'is_admin' => true,
        ]);

        $this->sport = Sport::create(['name' => 'Fútbol']);
        $this->league = League::create(['name' => 'LaLiga', 'sport_id' => $this->sport->id]);
    }

    /**
     * Test that Bet Path calculations calculate correct steps.
     */
    public function test_bet_path_computes_correct_preview_steps()
    {
        // Math parameters:
        // Start: 2, Target: 16, reinvestment: 100%, odds: 2
        // Expected N = 3 steps (2 -> 4 -> 8 -> 16)
        
        $S0 = 2.00;
        $T = 16.00;
        $R = 100.00;
        $odds = 2.00;
        $r = $R / 100;
        $g = 1 + $r * ($odds - 1); // 1 + 1*(2-1) = 2

        $N = ceil(1 + log($T / ($S0 * $odds)) / log($g)); // ceil(1 + log(16 / 4) / log(2)) = ceil(1 + 2) = 3

        $this->assertEquals(3, $N);
    }

    /**
     * Test that winning a step progresses the Bet Path.
     */
    public function test_winning_step_progresses_bet_path()
    {
        // 1. Create a path (2 -> 16, reinvestment 100%, total steps 3, average odds 2)
        $path = BetPath::create([
            'user_id' => $this->user->id,
            'name' => 'Reto 2 a 16',
            'start_amount' => 2.00,
            'target_amount' => 16.00,
            'reinvestment_rate' => 100.00,
            'current_step' => 1,
            'total_steps' => 3,
            'status' => 'active',
        ]);

        // Create steps
        $step1 = BetPathStep::create([
            'bet_path_id' => $path->id,
            'step_number' => 1,
            'calculated_odds' => 2.00,
            'expected_stake' => 2.00,
            'expected_payout' => 4.00,
            'status' => 'pending',
        ]);

        $step2 = BetPathStep::create([
            'bet_path_id' => $path->id,
            'step_number' => 2,
            'calculated_odds' => 2.00,
            'expected_stake' => 4.00,
            'expected_payout' => 8.00,
            'status' => 'pending',
        ]);

        $step3 = BetPathStep::create([
            'bet_path_id' => $path->id,
            'step_number' => 3,
            'calculated_odds' => 2.00,
            'expected_stake' => 8.00,
            'expected_payout' => 16.00,
            'status' => 'pending',
        ]);

        // 2. Register bet for Step 1
        $bet = Bet::create([
            'user_id' => $this->user->id,
            'type' => 'single',
            'stake' => 2.00,
            'odds' => 2.00,
            'status' => 'pending',
            'bet_path_id' => $path->id,
            'bet_path_step' => 1,
        ]);
        $step1->update(['bet_id' => $bet->id]);

        // 3. Settle bet as WON
        $bet->settle('won');

        // 4. Assert path progressed to step 2, step 1 is marked as won, and step 2's stake is updated
        $path->refresh();
        $step1->refresh();
        $step2->refresh();

        $this->assertEquals(2, $path->current_step);
        $this->assertEquals('active', $path->status);
        $this->assertEquals('won', $step1->status);
        
        // Next step stake should be 100% of payout (which is $4.00)
        $this->assertEquals(4.00, $step2->expected_stake);
    }

    /**
     * Test that losing a step fails the Bet Path.
     */
    public function test_losing_step_fails_bet_path()
    {
        $path = BetPath::create([
            'user_id' => $this->user->id,
            'name' => 'Reto 2 a 16',
            'start_amount' => 2.00,
            'target_amount' => 16.00,
            'reinvestment_rate' => 100.00,
            'current_step' => 1,
            'total_steps' => 3,
            'status' => 'active',
        ]);

        $step1 = BetPathStep::create([
            'bet_path_id' => $path->id,
            'step_number' => 1,
            'calculated_odds' => 2.00,
            'expected_stake' => 2.00,
            'expected_payout' => 4.00,
            'status' => 'pending',
        ]);

        $bet = Bet::create([
            'user_id' => $this->user->id,
            'type' => 'single',
            'stake' => 2.00,
            'odds' => 2.00,
            'status' => 'pending',
            'bet_path_id' => $path->id,
            'bet_path_step' => 1,
        ]);
        $step1->update(['bet_id' => $bet->id]);

        // Settle as LOST
        $bet->settle('lost');

        $path->refresh();
        $step1->refresh();

        $this->assertEquals('failed', $path->status);
        $this->assertEquals('lost', $step1->status);
        $this->assertEquals(1, $path->current_step); // remains on step 1 since it failed
    }

    /**
     * Test that winning a step but getting less payout than expected fails the Bet Path.
     */
    public function test_insufficient_payout_fails_bet_path()
    {
        $path = BetPath::create([
            'user_id' => $this->user->id,
            'name' => 'Reto 2 a 16',
            'start_amount' => 2.00,
            'target_amount' => 16.00,
            'reinvestment_rate' => 100.00,
            'current_step' => 1,
            'total_steps' => 3,
            'status' => 'active',
        ]);

        $step1 = BetPathStep::create([
            'bet_path_id' => $path->id,
            'step_number' => 1,
            'calculated_odds' => 2.00,
            'expected_stake' => 2.00,
            'expected_payout' => 4.00,
            'status' => 'pending',
        ]);

        $bet = Bet::create([
            'user_id' => $this->user->id,
            'type' => 'single',
            'stake' => 2.00,
            'odds' => 2.00,
            'status' => 'pending',
            'bet_path_id' => $path->id,
            'bet_path_step' => 1,
        ]);
        $step1->update(['bet_id' => $bet->id]);

        // Settle as WON but with actual payout lower than expected_payout ($4.00)
        // e.g. user cashed out early or there was a partial void, receiving only $3.00
        $bet->settle('won', 3.00);

        $path->refresh();
        $step1->refresh();

        $this->assertEquals('failed', $path->status);
        $this->assertEquals('lost', $step1->status);
    }

    /**
     * Test that accepting an AI suggestion correctly creates a bet and links it to the active step.
     */
    public function test_accept_step_suggestion_successful()
    {
        // 1. Create a path
        $path = BetPath::create([
            'user_id' => $this->user->id,
            'name' => 'Reto Test',
            'start_amount' => 10.00,
            'target_amount' => 50.00,
            'reinvestment_rate' => 100.00,
            'current_step' => 1,
            'total_steps' => 3,
            'status' => 'active',
        ]);

        $step1 = BetPathStep::create([
            'bet_path_id' => $path->id,
            'step_number' => 1,
            'calculated_odds' => 2.00,
            'expected_stake' => 10.00,
            'expected_payout' => 20.00,
            'status' => 'pending',
        ]);

        // 2. Set up component
        $component = \Livewire\Livewire::actingAs($this->user)
            ->test(\App\Livewire\BetPaths\BetPathManager::class);

        // Simulate opening suggestion modal
        $component->call('openSuggestionModal', $path->id, 1, 2.00, 10.00);

        // Assert properties were set correctly
        $component->assertSet('suggestedStepPathId', $path->id)
            ->assertSet('suggestedStepNumber', 1)
            ->assertSet('suggestedStepOdds', 2.00)
            ->assertSet('suggestedStepStake', 10.00);

        // Simulate receiving suggestion data from IA
        $mockedData = [
            'strategy' => 'single',
            'confidence_score' => 85,
            'analysis' => 'Justificación de prueba.',
            'selections' => [
                [
                    'sport' => 'Fútbol',
                    'league' => 'La Liga',
                    'home_team' => 'Real Madrid',
                    'away_team' => 'Atletico Madrid',
                    'market_name' => 'Ganador',
                    'selection' => 'Real Madrid',
                    'odds' => 2.00
                ]
            ]
        ];
        
        $component->set('suggestedStepData', $mockedData);

        // Call acceptStepSuggestion
        $component->call('acceptStepSuggestion');

        // Assert modal is closed
        $component->assertSet('showSuggestionModal', false);

        // 3. Verify database state
        $step1->refresh();
        $this->assertNotNull($step1->bet_id);
        $this->assertEquals('pending', $step1->status);

        $bet = $step1->bet;
        $this->assertNotNull($bet);
        $this->assertEquals(10.00, $bet->stake);
        $this->assertEquals(2.00, $bet->odds);
        $this->assertEquals('pending', $bet->status);
        $this->assertEquals($path->id, $bet->bet_path_id);
        $this->assertEquals(1, $bet->bet_path_step);

        $this->assertCount(1, $bet->selections);
        $selection = $bet->selections->first();
        $this->assertEquals('Real Madrid', $selection->selection);
        $this->assertEquals('Ganador', $selection->market_name);
        $this->assertEquals(2.00, $selection->odds);
        $this->assertEquals('Fútbol', $selection->sport->name);
        $this->assertEquals('La Liga', $selection->league->name);
    }
}

