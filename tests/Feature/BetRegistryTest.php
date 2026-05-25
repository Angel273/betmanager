<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Sport;
use App\Models\League;
use App\Models\Team;
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
}
