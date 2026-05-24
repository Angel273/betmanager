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

        $prompt = "Eres un analista experto en apuestas deportivas. Tu tarea es analizar la siguiente apuesta (o parlay combinada) e investigar por internet (utilizando tus capacidades de búsqueda en tiempo real) las probabilidades del mercado elegido basándote en la forma reciente de los equipos (últimos 5 partidos), enfrentamientos previos directos (H2H) de todos los partidos incluidos, lesiones, bajas de última hora y el valor de la cuota.\n\n";
        $prompt .= "Detalles de la apuesta:\n" . json_encode($betDetails, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
        $prompt .= "1. Evalúa el nivel de riesgo de la apuesta global (o parlay) y clasifícala estrictamente en uno de estos tres niveles de riesgo en minúsculas: 'segura', 'moderada', 'improbable'.\n";
        $prompt .= "2. Asigna una nota numérica de confianza del 1 al 100 para la apuesta global/parlay combinada (donde 100 es certeza absoluta y 1 es nula probabilidad) en la propiedad 'score'.\n";
        $prompt .= "3. Asigna una nota de confianza del 1 al 100 para cada una de las apuestas/selecciones individuales del parlay en la propiedad 'selection_scores' del JSON. Cada objeto de la lista debe tener 'selection_index' (1-based index) y 'score'.\n";
        $prompt .= "4. Redacta una justificación de análisis en español que sea sumamente concisa (máximo 4 líneas en total) detallando lo más crucial de tu investigación.\n";
        $prompt .= "5. Busca los últimos enfrentamientos directos (H2H) históricos y recientes para CADA uno de los partidos/selecciones incluidos en la lista (no te limites solo al primero, busca para todos los enfrentamientos del parlay). Devúélvelos estructurados en la propiedad 'h2h' del JSON. Incluye en cada elemento el nombre del partido correspondiente (ej: 'AC Milan vs Cagliari') en la propiedad 'match'.\n";
        $prompt .= "6. Analiza el mercado específico (`market_name`) y la selección (`selection`) de cada una de las apuestas de la lista. Investiga los datos estadísticos clave de los últimos 5 partidos de los equipos relevantes para ese tipo de mercado (ej: goles promedio si es Over/Under, tiros de esquina, tarjetas, resultados de victorias/derrotas si es 1X2). Devuelve este resumen estadístico de los últimos 5 partidos en la propiedad 'stats' del JSON, adaptado al mercado específico.\n\n";
        $prompt .= "IMPORTANTE: Tu respuesta debe ser ÚNICAMENTE un objeto JSON válido. No envíes bloques de código con markdown como ```json ... ```, simplemente retorna el texto del JSON con esta estructura exacta:\n";
        $prompt .= "{\n";
        $prompt .= "  \"risk\": \"segura|moderada|improbable\",\n";
        $prompt .= "  \"score\": 75,\n";
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
            if (!$json || !isset($json['risk']) || !isset($json['analysis'])) {
                // Fallback attempt in case the model returned a markdown codeblock
                $cleaned = preg_replace('/^```(?:json)?|```$/m', '', trim($resultText));
                $json = json_decode(trim($cleaned), true);
                
                if (!$json || !isset($json['risk']) || !isset($json['analysis'])) {
                    throw new Exception('La respuesta de la IA no se pudo parsear como JSON válido.');
                }
            }

            return [
                'risk' => strtolower(trim($json['risk'])),
                'score' => $json['score'] ?? null,
                'selection_scores' => $json['selection_scores'] ?? [],
                'analysis' => trim($json['analysis']),
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
}
