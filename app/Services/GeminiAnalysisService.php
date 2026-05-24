<?php

namespace App\Services;

use App\Models\Bet;
use Illuminate\Support\Facades\Http;
use Exception;

class GeminiAnalysisService
{
    protected $apiKey;
    protected $model;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
        // Default to gemini-3.1-flash-lite as requested
        $this->model = env('GEMINI_MODEL', 'gemini-3.1-flash-lite');
    }

    /**
     * Analyze a bet using Gemini 3.1 Flash-Lite.
     *
     * @param Bet $bet
     * @return array
     * @throws Exception
     */
    public function analyze(Bet $bet): array
    {
        if (empty($this->apiKey) || $this->apiKey === 'your-gemini-api-key') {
            throw new Exception('La API Key de Google AI Studio (Gemini) no está configurada en el archivo .env.');
        }

        // Format bet details for prompt
        $selectionsData = [];
        foreach ($bet->selections as $index => $sel) {
            $selectionsData[] = [
                'seleccion_numero' => $index + 1,
                'deporte' => $sel->sport->name,
                'liga' => $sel->league->name,
                'equipo_local' => $sel->teamHome?->name ?? 'N/A',
                'equipo_visitante' => $sel->teamAway?->name ?? 'N/A',
                'jugador' => $sel->player?->name ?? 'N/A',
                'mercado' => $sel->market_name,
                'seleccion_apostada' => $sel->selection,
                'cuota' => $sel->odds,
            ];
        }

        $betDetails = [
            'tipo_apuesta' => $bet->type === 'single' ? 'Individual' : 'Parlay/Combinada',
            'monto_apostado' => $bet->stake,
            'cuota_total' => $bet->odds,
            'selecciones' => $selectionsData,
        ];

        $currentDate = now()->format('d M Y');

        $prompt = "Eres un analista experto en apuestas deportivas. La fecha actual es {$currentDate}. Tu tarea es analizar la siguiente apuesta (o parlay combinada) e investigar por internet (utilizando tus capacidades de búsqueda en tiempo real con datos de la temporada actual 2025/2026 y del año 2026) las probabilidades del mercado elegido basándote en la forma reciente de los equipos (últimos 5 partidos más recientes jugados en 2026), enfrentamientos previos directos (H2H) de todos los partidos incluidos, lesiones o bajas de última hora, y el valor de la cuota. Asegúrate de ignorar datos obsoletos de años anteriores (como 2024 o 2023) a menos que sean estrictamente necesarios por falta de partidos nuevos, y prioriza siempre el año 2026.\n\n";
        $prompt .= "Detalles de la apuesta:\n" . json_encode($betDetails, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
        $prompt .= "1. Evalúa el nivel de riesgo de la apuesta global (o parlay) y clasifícala estrictamente en uno de estos tres niveles de riesgo en minúsculas: 'segura', 'moderada', 'improbable'.\n";
        $prompt .= "2. Asigna una nota numérica de confianza del 1 al 100 para la apuesta global/parlay combinada (donde 100 es certeza absoluta y 1 es nula probabilidad) en la propiedad 'score'.\n";
        $prompt .= "3. Asigna una nota de confianza del 1 al 100 para cada una de las apuestas/selecciones individuales del parlay en la propiedad 'selection_scores' del JSON. Cada objeto de la lista debe tener 'selection_index' (1-based index) y 'score'.\n";
        $prompt .= "4. Redacta una justificación de análisis en español que sea sumamente concisa (máximo 4 líneas en total) detallando lo más crucial de tu investigación en la propiedad 'analysis' del JSON.\n";
        $prompt .= "5. Busca los últimos enfrentamientos directos (H2H) históricos y recientes para CADA uno de los partidos/selecciones incluidos en la lista (no te limites solo al primero, busca para todos los enfrentamientos del parlay). Prioriza los enfrentamientos más recientes de 2025 y 2026. Devúélvelos estructurados en la propiedad 'h2h' del JSON. Incluye en cada elemento el nombre del partido correspondiente (ej: 'AC Milan vs Cagliari') en la propiedad 'match'.\n";
        $prompt .= "6. Analiza el mercado específico (`market_name`) y la selección (`selection`) de cada una de las apuestas de la lista. Investiga los datos estadísticos clave de los últimos 5 partidos más recientes jugados en 2026 de los equipos relevantes para ese tipo de mercado (ej: goles promedio si es Over/Under, tiros de esquina, tarjetas, resultados de victorias/derrotas si es 1X2). Devuelve este resumen estadístico de los últimos 5 partidos en la propiedad 'stats' del JSON, adaptado al mercado específico.\n\n";
        $prompt .= "IMPORTANTE: Tu respuesta debe ser ÚNICAMENTE un objeto JSON válido. No envíes bloques de código con markdown como ```json ... ```, simplemente retorna el texto del JSON con esta estructura exacta:\n";
        $prompt .= "{\n";
        $prompt .= "  \"risk\": \"segura|moderada|improbable\",\n";
        $prompt .= "  \"score\": 75,\n";
        $prompt .= "  \"analysis\": \"Justificación concisa del análisis en español (máximo 4 líneas)...\",\n";
        $prompt .= "  \"selection_scores\": [\n";
        $prompt .= "    {\n";
        $prompt .= "      \"selection_index\": 1,\n";
        $prompt .= "      \"score\": 82\n";
        $prompt .= "    }\n";
        $prompt .= "  ],\n";
        $prompt .= "  \"h2h\": [\n";
        $prompt .= "    {\n";
        $prompt .= "      \"match\": \"AC Milan vs Cagliari\",\n";
        $prompt .= "      \"home_team\": \"AC Milan\",\n";
        $prompt .= "      \"away_team\": \"Cagliari\",\n";
        $prompt .= "      \"score\": \"5 - 1\",\n";
        $prompt .= "      \"date\": \"11 May 2024\",\n";
        $prompt .= "      \"info\": \"Serie A\"\n";
        $prompt .= "    }\n";
        $prompt .= "  ],\n";
        $prompt .= "  \"stats\": {\n";
        $prompt .= "    \"market_type\": \"Goles|Corners|Tiros|Ganador|Otro\",\n";
        $prompt .= "    \"description\": \"Resumen de la tendencia (ej: City promedia 2.5 goles anotados...)\",\n";
        $prompt .= "    \"home_stats\": \"Estadísticas locales...\",\n";
        $prompt .= "    \"away_stats\": \"Estadísticas visitantes...\"\n";
        $prompt .= "  }\n";
        $prompt .= "}";

        // Call Gemini API
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json'
            ])->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json'
                ]
            ]);

            if ($response->failed()) {
                $errorMsg = $response->json('error.message') ?? 'Error en la API de Google Gemini.';
                throw new Exception($errorMsg);
            }

            $resultText = $response->json('candidates.0.content.parts.0.text');
            if (empty($resultText)) {
                throw new Exception('No se recibió texto de respuesta del modelo.');
            }

            // Parse response
            $json = json_decode(trim($resultText), true);
            if (!$json || !isset($json['risk'])) {
                // Fallback attempt in case the model returned a markdown codeblock
                $cleaned = preg_replace('/^```(?:json)?|```$/m', '', trim($resultText));
                $json = json_decode(trim($cleaned), true);
                
                if (!$json || !isset($json['risk'])) {
                    throw new Exception('La respuesta de la IA no se pudo parsear como JSON válido.');
                }
            }

            return [
                'risk' => strtolower(trim($json['risk'])),
                'score' => $json['score'] ?? null,
                'selection_scores' => $json['selection_scores'] ?? [],
                'analysis' => trim($json['analysis'] ?? 'Análisis no estructurado.'),
                'h2h' => $json['h2h'] ?? [],
                'stats' => $json['stats'] ?? null,
            ];

        } catch (Exception $e) {
            throw new Exception('Error al conectar con el servicio de análisis de IA: ' . $e->getMessage());
        }
    }

    /**
     * Parse a betting ticket image using Gemini 3.1 Flash-Lite.
     *
     * @param string $imagePath
     * @return array
     * @throws Exception
     */
    public function parseTicketImage(string $imagePath): array
    {
        if (empty($this->apiKey) || $this->apiKey === 'your-gemini-api-key') {
            throw new Exception('La API Key de Google AI Studio (Gemini) no está configurada en el archivo .env.');
        }

        if (!file_exists($imagePath)) {
            throw new Exception('El archivo de imagen del ticket no existe.');
        }

        $imageData = base64_encode(file_get_contents($imagePath));
        $mimeType = mime_content_type($imagePath);

        $prompt = "Analiza la captura de pantalla del ticket de apuestas deportiva adjunto. Extrae el monto apostado (stake), la cuota total, el tipo de apuesta ('single' o 'parlay') y la lista de selecciones. Para cada selección, identifica el deporte (Fútbol, Básquetbol, Béisbol, Hockey sobre Hielo), el equipo local (home_team), el equipo visitante (away_team), la selección realizada (qué se apostó, ej: 'Real Madrid', 'Más de 2.5', 'Lakers -5.5'), el mercado (ej: 'Ganador', 'Total de Goles', 'Hándicap') y la cuota individual de la selección.\n\n";
        $prompt .= "IMPORTANTE: Tu respuesta debe ser EXCLUSIVAMENTE un objeto JSON válido. No envíes bloques de código con markdown como ```json ... ```, simplemente retorna el texto del JSON con esta estructura exacta:\n";
        $prompt .= "{\n";
        $prompt .= "  \"type\": \"single|parlay\",\n";
        $prompt .= "  \"stake\": 100.00 (número o null si no se ve),\n";
        $prompt .= "  \"odds\": 2.50 (número o null si no se ve),\n";
        $prompt .= "  \"selections\": [\n";
        $prompt .= "    {\n";
        $prompt .= "      \"sport\": \"Fútbol|Básquetbol|Béisbol|Hockey sobre Hielo\",\n";
        $prompt .= "      \"home_team\": \"Nombre del equipo local en inglés o español\",\n";
        $prompt .= "      \"away_team\": \"Nombre del equipo visitante\",\n";
        $prompt .= "      \"market_name\": \"Nombre del mercado traducido al español estándar (ej: Ambos Anotan, Ganador, Más/Menos Goles, Hándicap, Total de Tiros, Tiros a Puerta)\",\n";
        $prompt .= "      \"selection\": \"La selección de apuesta exacta ej: Real Madrid, Más de 2.5, Lakers\",\n";
        $prompt .= "      \"odds\": 1.85 (número o null)\n";
        $prompt .= "    }\n";
        $prompt .= "  ]\n";
        $prompt .= "}";

        // Call Gemini API (Multimodal)
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json'
            ])->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                            [
                                'inlineData' => [
                                    'mimeType' => $mimeType,
                                    'data' => $imageData
                                ]
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json'
                ]
            ]);

            if ($response->failed()) {
                $errorMsg = $response->json('error.message') ?? 'Error en la API de Google Gemini.';
                \Log::error('Gemini ticket parsing API call failed', [
                    'status' => $response->status(),
                    'error' => $response->json(),
                    'body' => $response->body()
                ]);
                throw new Exception($errorMsg);
            }

            $resultText = $response->json('candidates.0.content.parts.0.text');
            if (empty($resultText)) {
                \Log::error('Gemini ticket parsing API returned empty response text', [
                    'raw_response' => $response->json()
                ]);
                throw new Exception('No se recibió texto de respuesta de la IA.');
            }

            // Parse response
            $json = json_decode(trim($resultText), true);
            if (!$json || !isset($json['selections'])) {
                // Fallback attempt in case the model returned a markdown codeblock
                $cleaned = preg_replace('/^```(?:json)?|```$/m', '', trim($resultText));
                $json = json_decode(trim($cleaned), true);
                
                if (!$json || !isset($json['selections'])) {
                    \Log::error('Gemini ticket parsing returned invalid JSON format', [
                        'raw_text' => $resultText,
                    ]);
                    throw new Exception('La respuesta de la IA no se pudo parsear como un JSON estructurado de ticket.');
                }
            }

            return $json;

        } catch (Exception $e) {
            \Log::error('Error in parseTicketImage service call: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            throw new Exception('Error al procesar la imagen del ticket: ' . $e->getMessage());
        }
    }

    /**
     * Search the web for upcoming bets matches filtering by sport, risk level and odds range.
     *
     * @param string|null $sport
     * @param string $risk
     * @param float|null $minOdds
     * @param float|null $maxOdds
     * @return array
     * @throws Exception
     */
    public function suggestBets(?string $sport = null, string $risk = 'segura', ?float $minOdds = null, ?float $maxOdds = null): array
    {
        if (empty($this->apiKey) || $this->apiKey === 'your-gemini-api-key') {
            throw new Exception('La API Key de Google AI Studio (Gemini) no está configurada en el archivo .env.');
        }

        $currentDate = now()->format('d M Y');
        $sportFilter = $sport ? "Deporte específico: {$sport}" : "Cualquier deporte (Fútbol, Básquetbol, Béisbol, Hockey sobre Hielo, Tenis, etc.)";
        $oddsFilter = ($minOdds || $maxOdds) ? "Cuota entre " . ($minOdds ?? 1.01) . " y " . ($maxOdds ?? 10.00) : "Cualquier cuota";

        $prompt = "Eres un buscador de apuestas experto impulsado por IA. La fecha actual es {$currentDate}. Tu tarea es buscar partidos reales programados para hoy, mañana o los próximos 3 días en el año 2026. Prioriza partidos de este año 2026 y encuentra 3 apuestas con alta probabilidad que cumplan con los siguientes criterios:\n\n";
        $prompt .= "- {$sportFilter}\n";
        $prompt .= "- Nivel de riesgo sugerido: '{$risk}' (puede ser 'segura', 'moderada' o 'improbable')\n";
        $prompt .= "- Rango de cuotas: {$oddsFilter}\n\n";
        $prompt .= "Investiga en internet utilizando tu capacidad de búsqueda web en tiempo real. Busca mercados reales (Ganador, Ambos Anotan, Over/Under de goles/puntos) y cuotas reales de casas de apuestas.\n\n";
        $prompt .= "IMPORTANTE: Tu respuesta debe ser ÚNICAMENTE un objeto JSON válido. No envíes bloques de código con markdown como ```json ... ```, simplemente retorna el texto del JSON con esta estructura exacta:\n";
        $prompt .= "{\n";
        $prompt .= "  \"recommendations\": [\n";
        $prompt .= "    {\n";
        $prompt .= "      \"sport\": \"Deporte (ej: Fútbol, Básquetbol)\",\n";
        $prompt .= "      \"league\": \"Nombre de la liga (ej: Premier League, NBA)\",\n";
        $prompt .= "      \"home_team\": \"Nombre del equipo local\",\n";
        $prompt .= "      \"away_team\": \"Nombre del equipo visitante\",\n";
        $prompt .= "      \"match_date\": \"Fecha en formato YYYY-MM-DD\",\n";
        $prompt .= "      \"market_name\": \"Mercado en español (ej: Ganador, Ambos Anotan, Total de Goles)\",\n";
        $prompt .= "      \"selection\": \"La selección recomendada exacta (ej: Real Madrid, Más de 2.5, Lakers -4.5)\",\n";
        $prompt .= "      \"odds\": 1.85,\n";
        $prompt .= "      \"confidence_score\": 85,\n";
        $prompt .= "      \"risk\": \"segura|moderada|improbable\",\n";
        $prompt .= "      \"analysis\": \"Explicación concisa en español de por qué es una buena apuesta (máximo 3 líneas)...\"\n";
        $prompt .= "    }\n";
        $prompt .= "  ]\n";
        $prompt .= "}";

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

        try {
            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'tools' => [
                    ['google_search' => new \stdClass()]
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json'
                ]
            ];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json'
            ])->post($url, $payload);

            if ($response->failed()) {
                $errorMsg = $response->json('error.message') ?? 'Error en la API de Google Gemini.';
                
                // Fallback if search grounding is restricted by quota or billing
                if (str_contains(strtolower($errorMsg), 'quota') || str_contains(strtolower($errorMsg), 'billing') || str_contains(strtolower($errorMsg), 'limit') || str_contains(strtolower($errorMsg), 'free')) {
                    \Log::warning('Search Grounding failed due to quota/billing. Retrying without Google Search tool.', ['error' => $errorMsg]);
                    
                    unset($payload['tools']);
                    $response = Http::withHeaders([
                        'Content-Type' => 'application/json'
                    ])->post($url, $payload);
                    
                    if ($response->failed()) {
                        $errorMsg = $response->json('error.message') ?? 'Error en la API de Google Gemini.';
                        throw new Exception($errorMsg);
                    }
                } else {
                    throw new Exception($errorMsg);
                }
            }

            $resultText = $response->json('candidates.0.content.parts.0.text');
            if (empty($resultText)) {
                throw new Exception('No se recibió texto de respuesta del buscador de apuestas.');
            }

            $json = json_decode(trim($resultText), true);
            if (!$json || !isset($json['recommendations'])) {
                $cleaned = preg_replace('/^```(?:json)?|```$/m', '', trim($resultText));
                $json = json_decode(trim($cleaned), true);
                if (!$json || !isset($json['recommendations'])) {
                    throw new Exception('La respuesta del buscador no se pudo parsear como JSON válido.');
                }
            }

            return $json['recommendations'];

        } catch (Exception $e) {
            \Log::error('Error en suggestBets: ' . $e->getMessage());
            throw new Exception('Error al obtener sugerencias de la IA: ' . $e->getMessage());
        }
    }

    /**
     * Suggest a bet or parlay targeting specific odds for a Bet Path step.
     *
     * @param float $targetOdds
     * @param string|null $sport
     * @return array
     * @throws Exception
     */
    public function suggestBetPathStep(float $targetOdds, ?string $sport = null): array
    {
        if (empty($this->apiKey) || $this->apiKey === 'your-gemini-api-key') {
            throw new Exception('La API Key de Google AI Studio (Gemini) no está configurada en el archivo .env.');
        }

        $currentDate = now()->format('d M Y');
        $sportFilter = $sport ? "Deporte preferido: {$sport}" : "Cualquier deporte";

        $prompt = "Eres un buscador de apuestas experto. La fecha actual es {$currentDate}. Tu tarea es buscar partidos reales programados para hoy, mañana o los próximos 3 días en el año 2026 utilizando tu capacidad de búsqueda web en tiempo real.\n\n";
        $prompt .= "Debes proponer una única apuesta individual o una combinación de hasta 3 apuestas (parlay) de alta probabilidad que sumen una cuota combinada aproximada a: x{$targetOdds} (con un margen aceptable de +/- 15%).\n";
        $prompt .= "{$sportFilter}.\n\n";
        $prompt .= "IMPORTANTE: Tu respuesta debe ser ÚNICAMENTE un objeto JSON válido. No envíes bloques de código con markdown como ```json ... ```, simplemente retorna el texto del JSON con esta estructura exacta:\n";
        $prompt .= "{\n";
        $prompt .= "  \"target_odds\": {$targetOdds},\n";
        $prompt .= "  \"strategy\": \"single|parlay\",\n";
        $prompt .= "  \"confidence_score\": 82,\n";
        $prompt .= "  \"analysis\": \"Justificación concisa de la estrategia en español...\",\n";
        $prompt .= "  \"selections\": [\n";
        $prompt .= "    {\n";
        $prompt .= "      \"sport\": \"Deporte\",\n";
        $prompt .= "      \"league\": \"Liga\",\n";
        $prompt .= "      \"home_team\": \"Equipo Local\",\n";
        $prompt .= "      \"away_team\": \"Equipo Visitante\",\n";
        $prompt .= "      \"match_date\": \"Fecha YYYY-MM-DD\",\n";
        $prompt .= "      \"market_name\": \"Mercado\",\n";
        $prompt .= "      \"selection\": \"Selección recomendada\",\n";
        $prompt .= "      \"odds\": 1.45\n";
        $prompt .= "    }\n";
        $prompt .= "  ]\n";
        $prompt .= "}";

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

        try {
            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'tools' => [
                    ['google_search' => new \stdClass()]
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json'
                ]
            ];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json'
            ])->post($url, $payload);

            if ($response->failed()) {
                $errorMsg = $response->json('error.message') ?? 'Error en la API de Google Gemini.';
                
                // Fallback if search grounding is restricted by quota or billing
                if (str_contains(strtolower($errorMsg), 'quota') || str_contains(strtolower($errorMsg), 'billing') || str_contains(strtolower($errorMsg), 'limit') || str_contains(strtolower($errorMsg), 'free')) {
                    \Log::warning('Bet Path search grounding failed due to quota/billing. Retrying without Google Search tool.', ['error' => $errorMsg]);
                    
                    unset($payload['tools']);
                    $response = Http::withHeaders([
                        'Content-Type' => 'application/json'
                    ])->post($url, $payload);
                    
                    if ($response->failed()) {
                        $errorMsg = $response->json('error.message') ?? 'Error en la API de Google Gemini.';
                        throw new Exception($errorMsg);
                    }
                } else {
                    throw new Exception($errorMsg);
                }
            }

            $resultText = $response->json('candidates.0.content.parts.0.text');
            if (empty($resultText)) {
                throw new Exception('No se recibió texto de respuesta del buscador de pasos.');
            }

            $json = json_decode(trim($resultText), true);
            if (!$json || !isset($json['selections'])) {
                $cleaned = preg_replace('/^```(?:json)?|```$/m', '', trim($resultText));
                $json = json_decode(trim($cleaned), true);
                if (!$json || !isset($json['selections'])) {
                    throw new Exception('La respuesta del buscador no se pudo parsear como JSON válido.');
                }
            }

            return $json;

        } catch (Exception $e) {
            \Log::error('Error en suggestBetPathStep: ' . $e->getMessage());
            throw new Exception('Error al obtener sugerencias de la IA: ' . $e->getMessage());
        }
    }
}

