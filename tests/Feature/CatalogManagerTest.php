<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Sport;
use App\Models\League;
use App\Models\Team;
use App\Livewire\Admin\CatalogManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogManagerTest extends TestCase
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
     * Test that admin can create multiple teams at once by passing a comma or newline separated list.
     */
    public function test_admin_can_bulk_create_teams()
    {
        Livewire::actingAs($this->adminUser)
            ->test(CatalogManager::class)
            ->set('activeTab', 'teams')
            ->set('team_league_id', $this->league->id)
            ->set('team_name', "Real Madrid, FC Barcelona\nAtletico de Madrid, Sevilla FC")
            ->call('saveTeam')
            ->assertHasNoErrors()
            ->assertSet('team_name', '');

        $this->assertDatabaseHas('teams', ['name' => 'Real Madrid', 'league_id' => $this->league->id]);
        $this->assertDatabaseHas('teams', ['name' => 'FC Barcelona', 'league_id' => $this->league->id]);
        $this->assertDatabaseHas('teams', ['name' => 'Atletico de Madrid', 'league_id' => $this->league->id]);
        $this->assertDatabaseHas('teams', ['name' => 'Sevilla FC', 'league_id' => $this->league->id]);

        $this->assertEquals(4, Team::where('league_id', $this->league->id)->count());
    }

    /**
     * Test that duplicate team names in the list are ignored to avoid duplicates.
     */
    public function test_bulk_create_ignores_duplicates()
    {
        // Pre-create Real Madrid
        Team::create(['name' => 'Real Madrid', 'league_id' => $this->league->id]);

        Livewire::actingAs($this->adminUser)
            ->test(CatalogManager::class)
            ->set('activeTab', 'teams')
            ->set('team_league_id', $this->league->id)
            ->set('team_name', "Real Madrid, FC Barcelona, Real Madrid")
            ->call('saveTeam')
            ->assertHasNoErrors();

        $this->assertEquals(2, Team::where('league_id', $this->league->id)->count());
        $this->assertDatabaseHas('teams', ['name' => 'FC Barcelona', 'league_id' => $this->league->id]);
    }
}
