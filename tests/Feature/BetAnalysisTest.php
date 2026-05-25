<?php

namespace Tests\Feature;

use App\Models\Bet;
use App\Models\User;
use App\Models\Sport;
use App\Models\League;
use App\Models\BetSelection;
use App\Livewire\Dashboard;
use App\Services\GeminiAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class BetAnalysisTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $sport;
    protected $league;
    protected $bet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Mauri Test',
            'email' => 'mauri@example.com',
            'is_admin' => true,
        ]);

        $this->sport = Sport::create(['name' => 'Fútbol']);
        $this->league = League::create(['name' => 'LaLiga', 'sport_id' => $this->sport->id]);
        
        $this->bet = Bet::create([
            'user_id' => $this->user->id,
            'type' => 'single',
            'stake' => 10.00,
            'odds' => 2.00,
            'status' => 'pending',
        ]);

        BetSelection::create([
            'bet_id' => $this->bet->id,
            'sport_id' => $this->sport->id,
            'league_id' => $this->league->id,
            'market_name' => 'Ganador',
            'selection' => 'Real Madrid',
            'odds' => 2.00,
            'status' => 'pending'
        ]);
    }

    /**
     * Test successful analysis with fully compliant JSON response.
     */
    public function test_successful_bet_analysis()
    {
        // Force the API Key check to pass
        $_ENV['GEMINI_API_KEY'] = 'test-api-key';
        $_SERVER['GEMINI_API_KEY'] = 'test-api-key';
        putenv('GEMINI_API_KEY=test-api-key');

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'risk' => 'segura',
                                        'score' => 90,
                                        'analysis' => 'Justificación de prueba.',
                                        'selection_scores' => [
                                            ['selection_index' => 1, 'score' => 90]
                                        ],
                                        'h2h' => [],
                                        'stats' => null
                                    ])
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        Livewire::actingAs($this->user)
            ->test(Dashboard::class)
            ->call('analyzeBet', $this->bet->id)
            ->assertHasNoErrors();

        $this->bet->refresh();
        $this->assertNotNull($this->bet->analyzed_at);
        $this->assertEquals('segura', $this->bet->ai_analysis['risk']);
        $this->assertEquals('Justificación de prueba.', $this->bet->ai_analysis['analysis']);
        $this->assertEquals(90, $this->bet->ai_analysis['score']);
    }

    /**
     * Test successful analysis where LLM response is missing the 'analysis' field but is handled by fallback.
     */
    public function test_bet_analysis_fallback_when_analysis_field_missing()
    {
        $_ENV['GEMINI_API_KEY'] = 'test-api-key';
        $_SERVER['GEMINI_API_KEY'] = 'test-api-key';
        putenv('GEMINI_API_KEY=test-api-key');

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'risk' => 'moderada',
                                        'score' => 70,
                                        // 'analysis' is missing!
                                        'selection_scores' => [],
                                        'h2h' => [],
                                        'stats' => null
                                    ])
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        Livewire::actingAs($this->user)
            ->test(Dashboard::class)
            ->call('analyzeBet', $this->bet->id)
            ->assertHasNoErrors();

        $this->bet->refresh();
        $this->assertNotNull($this->bet->analyzed_at);
        $this->assertEquals('moderada', $this->bet->ai_analysis['risk']);
        $this->assertEquals('Análisis no estructurado.', $this->bet->ai_analysis['analysis']);
    }

    /**
     * Test failure when GEMINI_API_KEY is not configured.
     */
    public function test_analysis_fails_when_api_key_placeholder()
    {
        $_ENV['GEMINI_API_KEY'] = 'your-gemini-api-key';
        $_SERVER['GEMINI_API_KEY'] = 'your-gemini-api-key';
        putenv('GEMINI_API_KEY=your-gemini-api-key');

        Livewire::actingAs($this->user)
            ->test(Dashboard::class)
            ->call('analyzeBet', $this->bet->id)
            ->assertSee('Error en el análisis de IA: La API Key de Google AI Studio (Gemini) no está configurada en el archivo .env.');
    }

    public function test_suggest_bets_successful()
    {
        $_ENV['GEMINI_API_KEY'] = 'test-api-key';
        $_SERVER['GEMINI_API_KEY'] = 'test-api-key';
        putenv('GEMINI_API_KEY=test-api-key');

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'recommendations' => [
                                            [
                                                'sport' => 'Fútbol',
                                                'league' => 'La Liga',
                                                'home_team' => 'Real Madrid',
                                                'away_team' => 'Barcelona',
                                                'match_date' => '2026-05-25',
                                                'market_name' => 'Ganador',
                                                'selection' => 'Real Madrid',
                                                'odds' => 1.85,
                                                'confidence_score' => 85,
                                                'risk' => 'segura',
                                                'analysis' => 'Real Madrid de local es fuerte.'
                                            ]
                                        ]
                                    ])
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        $service = new GeminiAnalysisService();
        $suggestions = $service->suggestBets('Fútbol', 'segura', 1.5, 2.0);

        $this->assertCount(1, $suggestions);
        $this->assertEquals('Real Madrid', $suggestions[0]['home_team']);
        $this->assertEquals(1.85, $suggestions[0]['odds']);
    }

    public function test_suggest_bet_path_step_successful()
    {
        $_ENV['GEMINI_API_KEY'] = 'test-api-key';
        $_SERVER['GEMINI_API_KEY'] = 'test-api-key';
        putenv('GEMINI_API_KEY=test-api-key');

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'target_odds' => 2.00,
                                        'strategy' => 'single',
                                        'confidence_score' => 82,
                                        'analysis' => 'Estrategia de prueba.',
                                        'selections' => [
                                            [
                                                'sport' => 'Fútbol',
                                                'league' => 'La Liga',
                                                'home_team' => 'Atletico Madrid',
                                                'away_team' => 'Sevilla',
                                                'match_date' => '2026-05-25',
                                                'market_name' => 'Ganador',
                                                'selection' => 'Atletico Madrid',
                                                'odds' => 2.00
                                            ]
                                        ]
                                    ])
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        \App\Models\BlacklistedLeague::create(['name' => 'Liga de Prueba Excluida']);

        $service = new GeminiAnalysisService();
        $result = $service->suggestBetPathStep(2.00, 'Fútbol', '2026-06-15');

        $this->assertEquals('single', $result['strategy']);
        $this->assertEquals(2.00, $result['target_odds']);
        $this->assertCount(1, $result['selections']);
        $this->assertEquals('Atletico Madrid', $result['selections'][0]['selection']);

        Http::assertSent(function ($request) {
            $promptText = $request['contents'][0]['parts'][0]['text'];
            return str_contains($promptText, '2026-06-15') && str_contains($promptText, 'Liga de Prueba Excluida');
        });
    }
}

