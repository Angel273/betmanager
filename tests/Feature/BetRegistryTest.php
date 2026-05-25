<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Sport;
use App\Models\League;
use App\Models\Team;
use App\Models\Bet;
use App\Models\BetSelection;
use App\Livewire\Bets\BetRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BetRegistryTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $normalUser;
    protected $sport;
    protected $league;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'is_admin' => true,
        ]);

        $this->normalUser = User::create([
            'name' => 'Normal User',
            'email' => 'user@example.com',
            'is_admin' => false,
        ]);

        $this->sport = Sport::create(['name' => 'Fútbol']);
        $this->league = League::create([
            'name' => 'LaLiga',
            'sport_id' => $this->sport->id,
        ]);
    }

    /**
     * Test that admin can successfully open the quick team modal and create a team.
     */
    public function test_admin_can_quick_add_team()
    {
        $testResult = Livewire::actingAs($this->adminUser)
            ->test(BetRegistry::class)
            ->set('selections.0.sport_id', $this->sport->id)
            ->set('selections.0.league_id', $this->league->id)
            ->call('openQuickTeamModal', 0, 'team_home_id')
            ->assertSet('showQuickTeamModal', true)
            ->assertSet('quickTeamLeagueId', $this->league->id)
            ->assertSet('quickTeamSelectionIndex', 0)
            ->assertSet('quickTeamField', 'team_home_id')
            ->set('quickTeamName', 'Real Madrid')
            ->call('saveQuickTeam')
            ->assertSet('showQuickTeamModal', false);

        $this->assertDatabaseHas('teams', [
            'name' => 'Real Madrid',
            'league_id' => $this->league->id,
        ]);

        $createdTeam = Team::where('name', 'Real Madrid')->first();
        $this->assertNotNull($createdTeam);
        
        // Assert team was auto-selected
        $this->assertEquals($createdTeam->id, $testResult->get('selections.0.team_home_id'));
    }

    /**
     * Test that normal user cannot open quick team modal or save quick team.
     */
    public function test_normal_user_cannot_quick_add_team()
    {
        // Try opening the modal
        Livewire::actingAs($this->normalUser)
            ->test(BetRegistry::class)
            ->set('selections.0.sport_id', $this->sport->id)
            ->set('selections.0.league_id', $this->league->id)
            ->call('openQuickTeamModal', 0, 'team_home_id')
            ->assertStatus(403);

        // Try directly saving a team
        Livewire::actingAs($this->normalUser)
            ->test(BetRegistry::class)
            ->set('quickTeamLeagueId', $this->league->id)
            ->set('quickTeamSelectionIndex', 0)
            ->set('quickTeamField', 'team_home_id')
            ->set('quickTeamName', 'FC Barcelona')
            ->call('saveQuickTeam')
            ->assertStatus(403);

        $this->assertDatabaseMissing('teams', [
            'name' => 'FC Barcelona',
        ]);
    }

    /**
     * Test that missing entities fallback modal can save new country, league, and teams,
     * and correctly populates them back into the selections array.
     */
    public function test_save_missing_entities_creates_records_and_populates_selections()
    {
        $testResult = Livewire::actingAs($this->adminUser)
            ->test(BetRegistry::class)
            ->set('tempSelections', [
                [
                    'sport_id' => $this->sport->id,
                    'league_id' => '',
                    'team_home_id' => '',
                    'team_away_id' => '',
                    'player_id' => '',
                    'market_name' => 'Ambos Anotan',
                    'selection' => 'Sí',
                    'odds' => 1.85,
                ]
            ])
            ->set('missingEntities', [
                [
                    'index' => 0,
                    'sport_id' => $this->sport->id,
                    'sport_name' => 'Fútbol',
                    'league_exists' => false,
                    'league_id' => null,
                    'league_name' => 'Serie A',
                    'country_id' => 'new',
                    'country_name' => 'Italia',
                    'new_country_name' => 'Italia',
                    'home_team_exists' => false,
                    'home_team_id' => null,
                    'home_team_name' => 'Juventus',
                    'away_team_exists' => false,
                    'away_team_id' => null,
                    'away_team_name' => 'Inter Milan',
                    'market_name' => 'Ambos Anotan',
                    'selection' => 'Sí',
                    'odds' => 1.85,
                ]
            ])
            ->set('showMissingEntitiesModal', true)
            ->call('saveMissingEntities')
            ->assertHasNoErrors()
            ->assertSet('showMissingEntitiesModal', false)
            ->assertSet('missingEntities', [])
            ->assertSet('tempSelections', []);

        // Assert records exist in DB
        $this->assertDatabaseHas('countries', ['name' => 'Italia']);
        $country = \App\Models\Country::where('name', 'Italia')->first();
        $this->assertNotNull($country);

        $this->assertDatabaseHas('leagues', [
            'name' => 'Serie A',
            'sport_id' => $this->sport->id,
            'country_id' => $country->id
        ]);
        $league = League::where('name', 'Serie A')->first();
        $this->assertNotNull($league);

        $this->assertDatabaseHas('teams', ['name' => 'Juventus', 'league_id' => $league->id]);
        $homeTeam = Team::where('name', 'Juventus')->first();
        $this->assertNotNull($homeTeam);

        $this->assertDatabaseHas('teams', ['name' => 'Inter Milan', 'league_id' => $league->id]);
        $awayTeam = Team::where('name', 'Inter Milan')->first();
        $this->assertNotNull($awayTeam);

        // Assert selections are populated with created IDs
        $selections = $testResult->get('selections');
        $this->assertCount(1, $selections);
        $this->assertEquals($this->sport->id, $selections[0]['sport_id']);
        $this->assertEquals($league->id, $selections[0]['league_id']);
        $this->assertEquals($homeTeam->id, $selections[0]['team_home_id']);
        $this->assertEquals($awayTeam->id, $selections[0]['team_away_id']);
    }

    /**
     * Test that user can edit an already registered bet and its selections are updated.
     */
    public function test_user_can_edit_existing_bet()
    {
        // 1. Create a bet with 1 selection
        $bet = Bet::create([
            'user_id' => $this->adminUser->id,
            'type' => 'single',
            'stake' => 100.00,
            'odds' => 1.85,
            'payout' => 0.00,
            'profit' => 0.00,
            'status' => 'pending',
            'notes' => 'Old note',
        ]);

        $selection = BetSelection::create([
            'bet_id' => $bet->id,
            'sport_id' => $this->sport->id,
            'league_id' => $this->league->id,
            'market_name' => 'Ambos Anotan',
            'selection' => 'Sí',
            'odds' => 1.85,
            'status' => 'pending',
        ]);

        // 2. Perform edit via Livewire BetRegistry
        Livewire::actingAs($this->adminUser)
            ->test(BetRegistry::class, ['bet' => $bet])
            ->assertSet('stake', 100.00)
            ->assertSet('notes', 'Old note')
            ->set('stake', 150.00)
            ->set('notes', 'New note')
            ->set('selections.0.odds', 2.10)
            ->call('saveBet')
            ->assertHasNoErrors();

        // 3. Verify in DB
        $bet->refresh();
        $this->assertEquals(150.00, $bet->stake);
        $this->assertEquals(2.10, $bet->odds);
        $this->assertEquals('New note', $bet->notes);

        $selection->refresh();
        $this->assertEquals(2.10, $selection->odds);
    }

    /**
     * Test parlay recalculation when selection statuses change.
     */
    public function test_parlay_recalculation_on_selection_status_change()
    {
        // 1. Create a parlay bet with 2 selections
        $bet = Bet::create([
            'user_id' => $this->adminUser->id,
            'type' => 'parlay',
            'stake' => 10.00,
            'odds' => 4.00,
            'payout' => 0.00,
            'profit' => 0.00,
            'status' => 'pending',
        ]);

        $selection1 = BetSelection::create([
            'bet_id' => $bet->id,
            'sport_id' => $this->sport->id,
            'league_id' => $this->league->id,
            'market_name' => 'Ambos Anotan',
            'selection' => 'Sí',
            'odds' => 2.00,
            'status' => 'pending',
        ]);

        $selection2 = BetSelection::create([
            'bet_id' => $bet->id,
            'sport_id' => $this->sport->id,
            'league_id' => $this->league->id,
            'market_name' => 'Ganador',
            'selection' => 'Real Madrid',
            'odds' => 2.00,
            'status' => 'pending',
        ]);

        // Reload selections relation
        $bet->load('selections');

        // 2. Set Selection 1 to won
        $selection1->update(['status' => 'won']);
        $bet->recalculate();

        $this->assertEquals('pending', $bet->status);
        $this->assertEquals(4.00, $bet->odds);
        $this->assertEquals(0.00, $bet->payout);

        // 3. Set Selection 2 to voided (cancelled)
        $selection2->update(['status' => 'voided']);
        $bet->refresh();
        $bet->recalculate();

        // Parlay should now be WON with odds x2.00, payout $20, profit $10
        $this->assertEquals('won', $bet->status);
        $this->assertEquals(2.00, $bet->odds);
        $this->assertEquals(20.00, floatval($bet->payout));
        $this->assertEquals(10.00, floatval($bet->profit));

        // 4. Set Selection 1 to lost
        $selection1->update(['status' => 'lost']);
        $bet->refresh();
        $bet->recalculate();

        // Parlay should now be LOST with payout $0, profit -$10
        $this->assertEquals('lost', $bet->status);
        $this->assertEquals(0.00, floatval($bet->payout));
        $this->assertEquals(-10.00, floatval($bet->profit));
    }
}
