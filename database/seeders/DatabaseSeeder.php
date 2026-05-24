<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Allowed Emails
        \App\Models\AllowedEmail::create([
            'email' => 'mauricioangel2736@gmail.com',
            'created_by' => 'System Seed'
        ]);

        // 2. Seed Regions
        $europa = \App\Models\Region::create(['name' => 'Europa']);
        $norteAmerica = \App\Models\Region::create(['name' => 'América del Norte']);
        $surAmerica = \App\Models\Region::create(['name' => 'América del Sur']);
        $internacional = \App\Models\Region::create(['name' => 'Internacional']);

        // 3. Seed Countries
        $espana = \App\Models\Country::create(['name' => 'España', 'region_id' => $europa->id]);
        $inglaterra = \App\Models\Country::create(['name' => 'Inglaterra', 'region_id' => $europa->id]);
        $italia = \App\Models\Country::create(['name' => 'Italia', 'region_id' => $europa->id]);
        $usa = \App\Models\Country::create(['name' => 'Estados Unidos', 'region_id' => $norteAmerica->id]);
        $mexico = \App\Models\Country::create(['name' => 'México', 'region_id' => $norteAmerica->id]);
        $canada = \App\Models\Country::create(['name' => 'Canadá', 'region_id' => $norteAmerica->id]);
        $brasil = \App\Models\Country::create(['name' => 'Brasil', 'region_id' => $surAmerica->id]);
        $argentina = \App\Models\Country::create(['name' => 'Argentina', 'region_id' => $surAmerica->id]);

        // 4. Seed Sports
        $futbol = \App\Models\Sport::create(['name' => 'Fútbol', 'icon' => 'futbol']);
        $basquetbol = \App\Models\Sport::create(['name' => 'Básquetbol', 'icon' => 'basketball']);
        $beisbol = \App\Models\Sport::create(['name' => 'Béisbol', 'icon' => 'baseball-bat']);
        $hockey = \App\Models\Sport::create(['name' => 'Hockey sobre Hielo', 'icon' => 'hockey-puck']);

        // 5. Seed Leagues
        // Fútbol Leagues
        $laliga = \App\Models\League::create(['name' => 'LaLiga', 'sport_id' => $futbol->id, 'country_id' => $espana->id]);
        $premier = \App\Models\League::create(['name' => 'Premier League', 'sport_id' => $futbol->id, 'country_id' => $inglaterra->id]);
        $champions = \App\Models\League::create(['name' => 'UEFA Champions League', 'sport_id' => $futbol->id, 'country_id' => null]);
        
        // Basketball Leagues
        $nba = \App\Models\League::create(['name' => 'NBA', 'sport_id' => $basquetbol->id, 'country_id' => $usa->id]);
        
        // Baseball Leagues
        $mlb = \App\Models\League::create(['name' => 'MLB', 'sport_id' => $beisbol->id, 'country_id' => $usa->id]);
        
        // Hockey Leagues
        $nhl = \App\Models\League::create(['name' => 'NHL', 'sport_id' => $hockey->id, 'country_id' => $usa->id]);

        // 6. Seed Teams
        // LaLiga Teams
        \App\Models\Team::create(['name' => 'Real Madrid', 'league_id' => $laliga->id]);
        \App\Models\Team::create(['name' => 'FC Barcelona', 'league_id' => $laliga->id]);
        \App\Models\Team::create(['name' => 'Atlético de Madrid', 'league_id' => $laliga->id]);

        // Premier League Teams
        \App\Models\Team::create(['name' => 'Manchester City', 'league_id' => $premier->id]);
        \App\Models\Team::create(['name' => 'Arsenal FC', 'league_id' => $premier->id]);
        \App\Models\Team::create(['name' => 'Liverpool FC', 'league_id' => $premier->id]);

        // NBA Teams
        \App\Models\Team::create(['name' => 'Los Angeles Lakers', 'league_id' => $nba->id]);
        \App\Models\Team::create(['name' => 'Golden State Warriors', 'league_id' => $nba->id]);
        \App\Models\Team::create(['name' => 'Boston Celtics', 'league_id' => $nba->id]);

        // MLB Teams
        \App\Models\Team::create(['name' => 'New York Yankees', 'league_id' => $mlb->id]);
        \App\Models\Team::create(['name' => 'Los Angeles Dodgers', 'league_id' => $mlb->id]);
        \App\Models\Team::create(['name' => 'Boston Red Sox', 'league_id' => $mlb->id]);

        // NHL Teams
        \App\Models\Team::create(['name' => 'Montreal Canadiens', 'league_id' => $nhl->id]);
        \App\Models\Team::create(['name' => 'Boston Bruins', 'league_id' => $nhl->id]);
        \App\Models\Team::create(['name' => 'Chicago Blackhawks', 'league_id' => $nhl->id]);
    }
}
