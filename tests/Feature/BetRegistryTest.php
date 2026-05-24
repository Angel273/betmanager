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
}
