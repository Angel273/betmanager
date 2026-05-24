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
        // Default to gemini-2.5-flash as requested
        $this->model = env('GEMINI_MODEL', 'gemini-2.5-flash');
    }

    /**
     * Analyze a bet using Gemini 2.5 Flash.
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

        $prompt = "Eres un analista experto en apuestas deportivas. Tu tarea es analizar la siguiente apuesta (o parlay combinada) e investigar por internet (utilizando tus capacidades de búsqueda en tiempo real) las probabilidades del mercado elegido basándote en la forma reciente de los equipos (últimos 5 partidos), enfrentamientos previos directos (H2H), lesiones, bajas de última hora y el valor de la cuota.\n\n";
        $prompt .= "Detalles de la apuesta:\n" . json_encode($betDetails, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
        $prompt .= "Evalúa el nivel de riesgo de la apuesta global (o parlay) y clasifícala estrictamente en uno de estos tres niveles de riesgo en minúsculas:\n";
        $prompt .= "- 'segura' (baja probabilidad de perder, equipos sólidos, forma excelente)\n";
        $prompt .= "- 'moderada' (riesgo aceptable, forma inestable pero con valor, o parlay mediano)\n";
        $prompt .= "- 'improbable' (riesgo muy alto, estadísticas contrarias, muchas bajas o parlay largo)\n\n";
        $prompt .= "Redacta una justificación de análisis en español que sea sumamente concisa (máximo 4 líneas en total) detallando lo más crucial de tu investigación.\n\n";
        $prompt .= "Busca además los últimos 3 enfrentamientos directos (H2H) históricos y recientes entre los equipos y devuélvelos de forma estructurada en la propiedad 'h2h' del JSON.\n\n";
        $prompt .= "IMPORTANTE: Tu respuesta debe ser ÚNICAMENTE un objeto JSON válido. No envíes bloques de código con markdown como ```json ... ```, simplemente retorna el texto del JSON con esta estructura exacta:\n";
        $prompt .= "{\n";
        $prompt .= "  \"risk\": \"segura|moderada|improbable\",\n";
        $prompt .= "  \"analysis\": \"Tu justificación concisa en español aquí...\",\n";
        $prompt .= "  \"h2h\": [\n";
        $prompt .= "    {\n";
        $prompt .= "      \"home_team\": \"Nombre del Equipo Local\",\n";
        $prompt .= "      \"away_team\": \"Nombre del Equipo Visitante\",\n";
        $prompt .= "      \"score\": \"3 - 1 (o resultado correspondiente)\",\n";
        $prompt .= "      \"date\": \"Fecha corta ej: 03 Abr 2024\",\n";
        $prompt .= "      \"info\": \"Liga/Torneo correspondiente\"\n";
        $prompt .= "    }\n";
        $prompt .= "  ]\n";
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
                'analysis' => trim($json['analysis']),
                'h2h' => $json['h2h'] ?? [],
            ];

        } catch (Exception $e) {
            throw new Exception('Error al conectar con el servicio de análisis de IA: ' . $e->getMessage());
        }
    }
}
