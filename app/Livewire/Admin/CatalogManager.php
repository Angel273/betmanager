<?php

namespace App\Livewire\Admin;

use App\Models\Region;
use App\Models\Country;
use App\Models\Sport;
use App\Models\League;
use App\Models\Team;
use App\Models\Player;
use Livewire\Component;
use Livewire\WithPagination;

class CatalogManager extends Component
{
    use WithPagination;

    public $activeTab = 'regions';
    public $search = '';
    public $isEditMode = false;
    public $selectedId = null;

    // Form fields
    // Region
    public $region_name = '';
    // Country
    public $country_name = '';
    public $country_region_id = '';
    // Sport
    public $sport_name = '';
    public $sport_icon = '';
    // League
    public $league_name = '';
    public $league_sport_id = '';
    public $league_country_id = '';
    // Team
    public $team_name = '';
    public $team_league_id = '';
    // Player
    public $player_name = '';
    public $player_team_id = '';

    public function mount()
    {
        // Protect page: Only Admin allowed
        abort_unless(auth()->check() && auth()->user()->is_admin, 403, 'Acceso no autorizado.');
    }

    public function changeTab($tab)
    {
        $this->activeTab = $tab;
        $this->search = '';
        $this->resetPage();
        $this->resetForms();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    private function resetForms()
    {
        $this->isEditMode = false;
        $this->selectedId = null;
        
        $this->reset([
            'region_name',
            'country_name', 'country_region_id',
            'sport_name', 'sport_icon',
            'league_name', 'league_sport_id', 'league_country_id',
            'team_name', 'team_league_id',
            'player_name', 'player_team_id'
        ]);
        $this->resetErrorBag();
    }

    // ==========================================
    // REGION CRUD
    // ==========================================
    public function saveRegion()
    {
        if ($this->isEditMode) {
            $this->validate(['region_name' => 'required|string|unique:regions,name,' . $this->selectedId]);
            $region = Region::findOrFail($this->selectedId);
            $region->update(['name' => trim($this->region_name)]);
            session()->flash('success', 'Región actualizada con éxito.');
        } else {
            $this->validate(['region_name' => 'required|string|unique:regions,name']);
            Region::create(['name' => trim($this->region_name)]);
            session()->flash('success', 'Región creada con éxito.');
        }
        $this->resetForms();
    }

    public function editRegion($id)
    {
        $this->resetForms();
        $region = Region::findOrFail($id);
        $this->selectedId = $region->id;
        $this->region_name = $region->name;
        $this->isEditMode = true;
    }

    public function deleteRegion($id)
    {
        Region::findOrFail($id)->delete();
        session()->flash('success', 'Región eliminada.');
        $this->resetForms();
    }

    // ==========================================
    // COUNTRY CRUD
    // ==========================================
    public function saveCountry()
    {
        $rules = [
            'country_name' => 'required|string',
            'country_region_id' => 'required|exists:regions,id'
        ];
        
        $this->validate($rules, [
            'country_name.required' => 'El nombre del país es obligatorio.',
            'country_region_id.required' => 'Debe seleccionar una región.',
            'country_region_id.exists' => 'La región seleccionada no es válida.'
        ]);

        if ($this->isEditMode) {
            $country = Country::findOrFail($this->selectedId);
            $country->update([
                'name' => trim($this->country_name),
                'region_id' => $this->country_region_id
            ]);
            session()->flash('success', 'País actualizado con éxito.');
        } else {
            Country::create([
                'name' => trim($this->country_name),
                'region_id' => $this->country_region_id
            ]);
            session()->flash('success', 'País creado con éxito.');
        }
        $this->resetForms();
    }

    public function editCountry($id)
    {
        $this->resetForms();
        $country = Country::findOrFail($id);
        $this->selectedId = $country->id;
        $this->country_name = $country->name;
        $this->country_region_id = $country->region_id;
        $this->isEditMode = true;
    }

    public function deleteCountry($id)
    {
        Country::findOrFail($id)->delete();
        session()->flash('success', 'País eliminado.');
        $this->resetForms();
    }

    // ==========================================
    // SPORT CRUD
    // ==========================================
    public function saveSport()
    {
        if ($this->isEditMode) {
            $this->validate([
                'sport_name' => 'required|string|unique:sports,name,' . $this->selectedId,
                'sport_icon' => 'nullable|string'
            ]);
            $sport = Sport::findOrFail($this->selectedId);
            $sport->update([
                'name' => trim($this->sport_name),
                'icon' => trim($this->sport_icon)
            ]);
            session()->flash('success', 'Deporte actualizado con éxito.');
        } else {
            $this->validate([
                'sport_name' => 'required|string|unique:sports,name',
                'sport_icon' => 'nullable|string'
            ]);
            Sport::create([
                'name' => trim($this->sport_name),
                'icon' => trim($this->sport_icon)
            ]);
            session()->flash('success', 'Deporte creado con éxito.');
        }
        $this->resetForms();
    }

    public function editSport($id)
    {
        $this->resetForms();
        $sport = Sport::findOrFail($id);
        $this->selectedId = $sport->id;
        $this->sport_name = $sport->name;
        $this->sport_icon = $sport->icon;
        $this->isEditMode = true;
    }

    public function deleteSport($id)
    {
        Sport::findOrFail($id)->delete();
        session()->flash('success', 'Deporte eliminado.');
        $this->resetForms();
    }

    // ==========================================
    // LEAGUE CRUD
    // ==========================================
    public function saveLeague()
    {
        $this->validate([
            'league_name' => 'required|string',
            'league_sport_id' => 'required|exists:sports,id',
            'league_country_id' => 'nullable|exists:countries,id'
        ], [
            'league_name.required' => 'El nombre de la liga es obligatorio.',
            'league_sport_id.required' => 'Debe seleccionar un deporte.'
        ]);

        if ($this->isEditMode) {
            $league = League::findOrFail($this->selectedId);
            $league->update([
                'name' => trim($this->league_name),
                'sport_id' => $this->league_sport_id,
                'country_id' => $this->league_country_id ?: null
            ]);
            session()->flash('success', 'Liga actualizada con éxito.');
        } else {
            League::create([
                'name' => trim($this->league_name),
                'sport_id' => $this->league_sport_id,
                'country_id' => $this->league_country_id ?: null
            ]);
            session()->flash('success', 'Liga creada con éxito.');
        }
        $this->resetForms();
    }

    public function editLeague($id)
    {
        $this->resetForms();
        $league = League::findOrFail($id);
        $this->selectedId = $league->id;
        $this->league_name = $league->name;
        $this->league_sport_id = $league->sport_id;
        $this->league_country_id = $league->country_id;
        $this->isEditMode = true;
    }

    public function deleteLeague($id)
    {
        League::findOrFail($id)->delete();
        session()->flash('success', 'Liga eliminada.');
        $this->resetForms();
    }

    // ==========================================
    // TEAM CRUD
    // ==========================================
    public function saveTeam()
    {
        $this->validate([
            'team_name' => 'required|string',
            'team_league_id' => 'required|exists:leagues,id'
        ], [
            'team_name.required' => 'El nombre del equipo es obligatorio.',
            'team_league_id.required' => 'Debe seleccionar una liga.'
        ]);

        if ($this->isEditMode) {
            $team = Team::findOrFail($this->selectedId);
            $team->update([
                'name' => trim($this->team_name),
                'league_id' => $this->team_league_id
            ]);
            session()->flash('success', 'Equipo actualizado.');
        } else {
            // Split by commas and/or newlines
            $names = preg_split('/[\n,]+/', $this->team_name);
            $createdCount = 0;

            foreach ($names as $name) {
                $trimmedName = trim($name);
                if (!empty($trimmedName)) {
                    // Check if it already exists to avoid duplicates
                    Team::firstOrCreate([
                        'name' => $trimmedName,
                        'league_id' => $this->team_league_id
                    ]);
                    $createdCount++;
                }
            }

            if ($createdCount > 1) {
                session()->flash('success', "$createdCount equipos creados con éxito.");
            } else {
                session()->flash('success', 'Equipo creado.');
            }
        }
        $this->resetForms();
    }

    public function editTeam($id)
    {
        $this->resetForms();
        $team = Team::findOrFail($id);
        $this->selectedId = $team->id;
        $this->team_name = $team->name;
        $this->team_league_id = $team->league_id;
        $this->isEditMode = true;
    }

    public function deleteTeam($id)
    {
        Team::findOrFail($id)->delete();
        session()->flash('success', 'Equipo eliminado.');
        $this->resetForms();
    }

    // ==========================================
    // PLAYER CRUD
    // ==========================================
    public function savePlayer()
    {
        $this->validate([
            'player_name' => 'required|string',
            'player_team_id' => 'nullable|exists:teams,id'
        ], [
            'player_name.required' => 'El nombre del jugador es obligatorio.'
        ]);

        if ($this->isEditMode) {
            $player = Player::findOrFail($this->selectedId);
            $player->update([
                'name' => trim($this->player_name),
                'team_id' => $this->player_team_id ?: null
            ]);
            session()->flash('success', 'Jugador actualizado.');
        } else {
            Player::create([
                'name' => trim($this->player_name),
                'team_id' => $this->player_team_id ?: null
            ]);
            session()->flash('success', 'Jugador creado.');
        }
        $this->resetForms();
    }

    public function editPlayer($id)
    {
        $this->resetForms();
        $player = Player::findOrFail($id);
        $this->selectedId = $player->id;
        $this->player_name = $player->name;
        $this->player_team_id = $player->team_id;
        $this->isEditMode = true;
    }

    public function deletePlayer($id)
    {
        Player::findOrFail($id)->delete();
        session()->flash('success', 'Jugador eliminado.');
        $this->resetForms();
    }

    public function render()
    {
        $data = [];
        $searchQuery = '%' . $this->search . '%';

        // Load dependent dropdown data
        $regionsList = Region::orderBy('name')->get();
        $sportsList = Sport::orderBy('name')->get();
        $countriesList = Country::orderBy('name')->get();
        $leaguesList = League::orderBy('name')->get();
        $teamsList = Team::orderBy('name')->get();

        switch ($this->activeTab) {
            case 'regions':
                $data['items'] = Region::where('name', 'like', $searchQuery)
                    ->orderBy('name')
                    ->paginate(10);
                break;
            case 'countries':
                $data['items'] = Country::with('region')
                    ->where('name', 'like', $searchQuery)
                    ->orWhereHas('region', function ($q) use ($searchQuery) {
                        $q->where('name', 'like', $searchQuery);
                    })
                    ->orderBy('name')
                    ->paginate(10);
                break;
            case 'sports':
                $data['items'] = Sport::where('name', 'like', $searchQuery)
                    ->orderBy('name')
                    ->paginate(10);
                break;
            case 'leagues':
                $data['items'] = League::with(['sport', 'country'])
                    ->where('name', 'like', $searchQuery)
                    ->orWhereHas('sport', function ($q) use ($searchQuery) {
                        $q->where('name', 'like', $searchQuery);
                    })
                    ->orderBy('name')
                    ->paginate(10);
                break;
            case 'teams':
                $data['items'] = Team::with('league.sport')
                    ->where('name', 'like', $searchQuery)
                    ->orWhereHas('league', function ($q) use ($searchQuery) {
                        $q->where('name', 'like', $searchQuery);
                    })
                    ->orderBy('name')
                    ->paginate(10);
                break;
            case 'players':
                $data['items'] = Player::with('team.league')
                    ->where('name', 'like', $searchQuery)
                    ->orWhereHas('team', function ($q) use ($searchQuery) {
                        $q->where('name', 'like', $searchQuery);
                    })
                    ->orderBy('name')
                    ->paginate(10);
                break;
        }

        return view('livewire.admin.catalog-manager', array_merge($data, [
            'regionsList' => $regionsList,
            'sportsList' => $sportsList,
            'countriesList' => $countriesList,
            'leaguesList' => $leaguesList,
            'teamsList' => $teamsList,
        ]));
    }
}
