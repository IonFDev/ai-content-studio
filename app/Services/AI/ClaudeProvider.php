<?php

namespace App\Services\AI;

use App\Contracts\AI\AIProvider;
use App\Models\Project;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ClaudeProvider implements AIProvider
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';

    private const MODEL = 'claude-sonnet-4-6';

    public function generateContent(
        Project $project,
        string $transcript
    ): array {
        $apiKey = config('youtube_studio.anthropic.api_key');

        if (!$apiKey) {
            throw new RuntimeException(
                'ANTHROPIC_API_KEY no está configurada.'
            );
        }

        $model = config(
            'youtube_studio.anthropic.model',
            self::MODEL
        );

        $timeout = (int) config(
            'youtube_studio.anthropic.timeout',
            300
        );

        $payload = [
            'model' => $model,

            'max_tokens' => 18000,

            'system' => $this->buildSystemPrompt(),

            'messages' => [
                [
                    'role' => 'user',
                    'content' => $this->buildUserPrompt(
                        $project,
                        $transcript
                    ),
                ],
            ],

            'output_config' => [
                'format' => [
                    'type' => 'json_schema',
                    'schema' => $this->getOutputSchema(),
                ],
            ],

            'stream' => true,
        ];

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'accept' => 'text/event-stream',
                'content-type' => 'application/json',
            ])
                ->timeout($timeout)
                ->withOptions([
                    'stream' => true,
                ])
                ->post(self::API_URL, $payload);
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'No se pudo conectar con Anthropic: '
                . $exception->getMessage(),
                0,
                $exception
            );
        }

        if (!$response->successful()) {
            $this->throwApiException($response);
        }

        $text = $this->consumeStream($response);

        $text = $this->sanitizeJsonControlCharacters($text);

        $logPath = storage_path('logs/claude-response.txt');

        file_put_contents(
            $logPath,
            $text . PHP_EOL . PHP_EOL,
            FILE_APPEND
        );

        try {
            $data = json_decode(
                $text,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException $exception) {
            throw new RuntimeException(
                'Claude ha devuelto contenido que no es JSON válido: '
                . $exception->getMessage()
                . ' Longitud: '
                . strlen($text),
                0,
                $exception
            );
        }

        $this->validateResponse($data);

        return $data;
    }

    private function buildSystemPrompt(): string
    {
        return <<<'PROMPT'
Eres el director creativo, guionista y storyboard artist de un canal de YouTube de actualidad económica y financiera.

Tu trabajo es transformar una fuente/transcripción en un vídeo entretenido, claro y visualmente potente.

NO eres el generador de imágenes. Tú decides qué debe mostrar cada escena. La aplicación posterior se encargará de aplicar el estilo visual global y generar las imágenes con FLUX.

========================================
OBJETIVO DEL VÍDEO
========================================

Crear vídeos de aproximadamente 8–10 minutos.

El guion debe:

- explicar economía y finanzas de forma sencilla;
- ser entretenido para una persona que no sea experta;
- mantener ritmo;
- utilizar ejemplos y analogías;
- evitar tono académico;
- evitar relleno;
- introducir un hook fuerte;
- desarrollar una idea de forma progresiva;
- terminar con una conclusión clara.

El guion debe estar en español.

========================================
ESTRUCTURA NARRATIVA
========================================

Prioriza:

1. Hook.
2. Contexto.
3. Qué está ocurriendo.
4. Por qué importa.
5. Cómo funciona.
6. Ejemplos o consecuencias.
7. Qué puede ocurrir después.
8. Conclusión.

No inventes datos, porcentajes, empresas, declaraciones o acontecimientos que no aparezcan en la fuente o que no puedan deducirse razonablemente de ella.

========================================
ESCENAS
========================================

Genera aproximadamente 40–50 escenas para un vídeo de 8–10 minutos.

No crees una escena nueva por cada frase.

Cada escena debe representar una unidad visual útil de la narración.

Las escenas deben variar visualmente.

No repitas continuamente:

- Detective de pie;
- Detective mirando a cámara;
- personaje centrado sobre fondo vacío;
- gráfico genérico;
- grupo de stickmen sin contexto.

Busca metáforas visuales, situaciones editoriales y composiciones interesantes.

La imagen debe funcionar como apoyo visual de la narración, no como una simple ilustración literal de cada frase.

========================================
UNIVERSO VISUAL
========================================

El canal utiliza una estética de:

"Editorial cartoon illustration with a minimalist stickman visual language."

Es un universo de ilustración 2D coherente.

Todos estos elementos pertenecen al mismo universo gráfico:

- personajes;
- edificios;
- vehículos;
- oficinas;
- bancos;
- máquinas;
- dinero;
- gráficos;
- objetos;
- mobiliario;
- ciudades;
- paisajes;
- símbolos económicos.

El estilo debe sentirse como una ilustración editorial/cartoon para adultos.

Debe tener:

- líneas negras limpias y controladas;
- formas geométricas simplificadas;
- siluetas expresivas;
- composición sofisticada;
- profundidad mediante primer plano, plano medio y fondo;
- perspectiva;
- superposición;
- contraste de escala;
- diagonales;
- asimetría;
- detalle visual moderado.

NO debe parecer:

- contenido para niños pequeños;
- dibujo infantil;
- chibi;
- kawaii;
- libro infantil;
- iconos corporativos;
- clipart;
- imagen vacía;
- hiperrealismo;
- fotografía;
- 3D;
- anime;
- Pixar;
- videojuego.

El resultado debe ser visualmente atractivo sin convertirse en una ilustración hipercompleja.

========================================
PERSONAJES
========================================

Existen dos tipos de personajes.

DETECTIVE:

Es el personaje recurrente del canal.

Es un stickman detective con:

- cabeza circular blanca;
- ojos negros sencillos;
- boca sencilla y expresiva;
- gabardina negra;
- sombrero fedora negro;
- cuerpo delgado;
- lupa cuando sea apropiado.

Las referencias del Detective sirven exclusivamente para conservar su identidad visual.

No debes copiar el encuadre de las referencias.

El Detective NO debe dominar automáticamente la imagen.

Normalmente debe ocupar aproximadamente entre un 15% y un 40% de la altura de la imagen.

En planos muy abiertos puede ser mucho más pequeño.

Úsalo cuando aporte algo narrativamente.

GENERIC STICKMEN:

Son personajes anónimos.

Deben ser simples figuras stickman con:

- cabeza circular;
- cuerpo lineal;
- apariencia anónima;
- sin ropa característica;
- sin sombrero;
- sin lupa;
- sin accesorios de detective;
- sin elementos que puedan confundirlos con el Detective.

MUY IMPORTANTE:

Cuando una escena utilice generic_stickmen, NO debe parecer que todos son versiones del Detective.

========================================
CHARACTER ROLE
========================================

Cada escena debe elegir exactamente uno:

none
generic_stickmen
detective
detective_and_generic_stickmen

Usa:

none
cuando no sea necesario mostrar personajes.

generic_stickmen
cuando quieras representar personas anónimas, trabajadores, consumidores, inversores, ciudadanos, políticos genéricos, etc.

detective
cuando el Detective sea el protagonista visual.

detective_and_generic_stickmen
cuando necesites al Detective junto a otros personajes anónimos.

No uses al Detective simplemente porque existe en el canal.

========================================
COMPOSICIÓN
========================================

Cada escena debe elegir un tipo de plano:

wide_establishing
wide
medium_wide
medium
close_up
extreme_close_up
top_down
low_angle

Prioriza:

wide_establishing
wide
medium_wide
medium

Utiliza close_up y extreme_close_up solamente cuando aporten impacto.

Busca composiciones donde el espectador pueda entender rápidamente:

- cuál es el elemento principal;
- qué está ocurriendo;
- dónde debe mirar;
- qué relación existe entre los elementos.

========================================
METÁFORAS VISUALES
========================================

Cuando sea apropiado, utiliza metáforas visuales editoriales.

Ejemplos:

- una economía atrapada dentro de una caja de cristal;
- un enorme mecanismo de tipos de interés;
- un stickman intentando subir una montaña de facturas;
- una subasta caótica representando los mercados;
- una balanza entre deuda y crecimiento;
- un edificio corporativo proyectando una enorme sombra;
- una carretera formada por un gráfico bursátil;
- una impresora gigante de dinero;
- una empresa representada como un barco durante una tormenta.

No fuerces una metáfora si una representación directa es mejor.

========================================
DATOS Y TEXTO
========================================

No inventes texto visible dentro de las imágenes.

No dependas de que FLUX escriba correctamente:

- porcentajes;
- cifras;
- nombres;
- titulares;
- etiquetas;
- tablas;
- gráficos;
- flechas;
- estadísticas.

Cuando la imagen necesite información exacta, indícalo en manual_elements para añadirlo posteriormente en postproducción.

========================================
IMAGE PROMPT
========================================

image_prompt debe estar en inglés.

Debe describir únicamente la escena concreta:

- sujeto;
- acción;
- entorno;
- objetos;
- composición;
- cámara;
- metáfora;
- jerarquía visual.

No escribas un Style Bible enorme dentro de cada prompt.

El sistema añadirá posteriormente el estilo visual global.

El prompt debe ser específico y visual.

Evita prompts genéricos como:

"stickman in an office".

Prefiere algo equivalente a:

"Wide editorial scene inside a stylized central bank control room. An enormous mechanical interest-rate lever dominates the center of the room while several anonymous stickmen struggle to move it. The Detective appears small in the lower-right corner examining the mechanism with his magnifying glass. Strong depth with foreground machinery, a large central mechanism and simplified architectural elements in the background."

========================================
PRODUCCIÓN
========================================

manual_elements contiene únicamente elementos que deberían añadirse o corregirse posteriormente porque requieren precisión.

Ejemplos:

- "Añadir 4.5% junto al indicador de inflación."
- "Añadir nombre de la empresa sobre el edificio."
- "Añadir flecha descendente roja."
- "Añadir valores exactos del gráfico."

animation_notes puede indicar movimientos simples:

- zoom;
- pan;
- desplazamiento;
- aparición de elementos;
- énfasis.

production_notes contiene observaciones útiles para edición.

========================================
IMPORTANTE
========================================

La narración de las escenas debe cubrir todo el guion.

No omitas partes importantes.

No inventes acontecimientos.

No hagas todas las escenas visualmente iguales.

Prioriza variedad, metáforas, profundidad y composición editorial.

Devuelve exclusivamente el JSON solicitado.
PROMPT;
    }

    private function buildUserPrompt(
        Project $project,
        string $transcript
    ): string {
        return <<<PROMPT
Crea el contenido completo del siguiente vídeo.

PROYECTO:
{$project->name}

TÍTULO DE LA FUENTE:
{$project->source_title}

URL:
{$project->source_url}

TRANSCRIPCIÓN:
{$transcript}

Genera:

- título principal;
- títulos alternativos;
- descripción;
- keywords;
- hashtags;
- concepto de miniatura;
- texto de miniatura;
- hook;
- guion completo;
- estructura;
- 40–50 escenas;
- información de producción.

La narración de las escenas debe corresponder al guion completo.

Las escenas deben ser visualmente variadas y seguir las reglas del sistema.

No añadas explicaciones fuera del JSON.
PROMPT;
    }

    private function getOutputSchema(): array
    {
        return [
            'type' => 'object',

            'additionalProperties' => false,

            'properties' => [
                'video' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'properties' => [
                        'title' => [
                            'type' => 'string',
                        ],
                        'alternative_titles' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'string',
                            ],
                        ],
                        'description' => [
                            'type' => 'string',
                        ],
                        'keywords' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'string',
                            ],
                        ],
                        'hashtags' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'string',
                            ],
                        ],
                        'thumbnail_concept' => [
                            'type' => 'string',
                        ],
                        'thumbnail_text' => [
                            'type' => 'string',
                        ],
                    ],
                    'required' => [
                        'title',
                        'alternative_titles',
                        'description',
                        'keywords',
                        'hashtags',
                        'thumbnail_concept',
                        'thumbnail_text',
                    ],
                ],

                'content' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'properties' => [
                        'hook' => [
                            'type' => 'string',
                        ],
                        'script' => [
                            'type' => 'string',
                        ],
                        'structure' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                    'required' => [
                        'hook',
                        'script',
                        'structure',
                    ],
                ],

                'scenes' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'order' => [
                                'type' => 'integer',
                            ],
                            'narration' => [
                                'type' => 'string',
                            ],
                            'visual_description' => [
                                'type' => 'string',
                            ],
                            'background' => [
                                'type' => 'string',
                            ],
                            'characters' => [
                                'type' => 'string',
                            ],
                            'character_role' => [
                                'type' => 'string',
                            ],
                            'camera' => [
                                'type' => 'string',
                            ],
                            'visual_metaphor' => [
                                'type' => 'string',
                            ],
                            'image_prompt' => [
                                'type' => 'string',
                            ],
                            'manual_elements' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'string',
                                ],
                            ],
                            'animation_notes' => [
                                'type' => 'string',
                            ],
                            'production_notes' => [
                                'type' => 'string',
                            ],
                        ],
                        'required' => [
                            'order',
                            'narration',
                            'visual_description',
                            'background',
                            'characters',
                            'character_role',
                            'camera',
                            'visual_metaphor',
                            'image_prompt',
                            'manual_elements',
                            'animation_notes',
                            'production_notes',
                        ],
                    ],
                ],
            ],

            'required' => [
                'video',
                'content',
                'scenes',
            ],
        ];
    }

    private function validateResponse(array $data): void
    {
        foreach ([
            'video',
            'content',
            'scenes',
        ] as $section) {
            if (!array_key_exists($section, $data)) {
                throw new RuntimeException(
                    "La respuesta de Claude no contiene la sección '{$section}'."
                );
            }
        }

        if (!is_array($data['video'])) {
            throw new RuntimeException(
                'La sección video no es válida.'
            );
        }

        if (!is_array($data['content'])) {
            throw new RuntimeException(
                'La sección content no es válida.'
            );
        }

        if (!is_array($data['scenes'])) {
            throw new RuntimeException(
                'La sección scenes no es válida.'
            );
        }

        $sceneCount = count($data['scenes']);

        if ($sceneCount < 30 || $sceneCount > 60) {
            throw new RuntimeException(
                "Claude ha generado {$sceneCount} escenas. "
                . 'Se esperaban entre 30 y 60.'
            );
        }

        foreach ($data['scenes'] as $index => $scene) {
            if (!is_array($scene)) {
                throw new RuntimeException(
                    "La escena {$index} no es válida."
                );
            }

            foreach ([
                'order',
                'narration',
                'visual_description',
                'character_role',
                'camera',
                'image_prompt',
            ] as $field) {
                if (!array_key_exists($field, $scene)) {
                    throw new RuntimeException(
                        "La escena {$index} no contiene '{$field}'."
                    );
                }
            }

            $role = $scene['character_role'];

            if (!in_array($role, [
                'none',
                'generic_stickmen',
                'detective',
                'detective_and_generic_stickmen',
            ], true)) {
                throw new RuntimeException(
                    "La escena {$index} tiene un character_role inválido: {$role}"
                );
            }

            $camera = $scene['camera'];

            if (!in_array($camera, Scene::SHOT_TYPES, true)) {
                throw new RuntimeException(
                    "La escena {$index} tiene un camera inválido: {$camera}"
                );
            }

            if (
                !is_string($scene['narration'])
                || trim($scene['narration']) === ''
            ) {
                throw new RuntimeException(
                    "La escena {$index} no contiene narración."
                );
            }

            if (
                !is_string($scene['image_prompt'])
                || trim($scene['image_prompt']) === ''
            ) {
                throw new RuntimeException(
                    "La escena {$index} no contiene image_prompt."
                );
            }
        }

        $script = trim(
            (string) ($data['content']['script'] ?? '')
        );

        if ($script === '') {
            throw new RuntimeException(
                'Claude no ha generado ningún guion.'
            );
        }

        $wordCount = str_word_count(
            strip_tags($script)
        );

        if ($wordCount < 900 || $wordCount > 1800) {
            throw new RuntimeException(
                "El guion generado tiene {$wordCount} palabras. "
                . 'Se esperaba aproximadamente entre 900 y 1800.'
            );
        }
    }

    private function consumeStream($response): string
    {
        $body = $response->toPsrResponse()->getBody();

        $buffer = '';
        $result = '';

        while (!$body->eof()) {
            $chunk = $body->read(8192);

            if ($chunk === '') {
                continue;
            }

            $buffer .= $chunk;

            while (($separator = strpos($buffer, "\n\n")) !== false) {
                $event = substr($buffer, 0, $separator);

                $buffer = substr(
                    $buffer,
                    $separator + 2
                );

                $result .= $this->parseSseEvent($event);
            }
        }

        if (trim($buffer) !== '') {
            $result .= $this->parseSseEvent($buffer);
        }

        return trim($result);
    }

    private function parseSseEvent(string $event): string
    {
        $lines = preg_split(
            "/\r\n|\r|\n/",
            $event
        );

        $dataLines = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if (
                $line === ''
                || str_starts_with($line, 'event:')
            ) {
                continue;
            }

            if (str_starts_with($line, 'data:')) {
                $dataLines[] = trim(
                    substr($line, 5)
                );
            }
        }

        if (empty($dataLines)) {
            return '';
        }

        $data = implode("\n", $dataLines);

        if ($data === '[DONE]') {
            return '';
        }

        $decoded = json_decode($data, true);

        if (!is_array($decoded)) {
            return '';
        }

        if (
            isset($decoded['type'])
            && $decoded['type'] === 'error'
        ) {
            $message = $decoded['error']['message']
                ?? 'Error desconocido de Anthropic.';

            throw new RuntimeException(
                'Anthropic SSE error: ' . $message
            );
        }

        if (
            ($decoded['type'] ?? null) === 'content_block_delta'
            && ($decoded['delta']['type'] ?? null) === 'text_delta'
        ) {
            return (string) (
                $decoded['delta']['text'] ?? ''
            );
        }

        return '';
    }

    private function sanitizeJsonControlCharacters(
        string $text
    ): string {
        return preg_replace_callback(
            '/[\x00-\x1F\x7F]/',
            function ($match) {
                return match ($match[0]) {
                    "\n", "\r", "\t" => $match[0],
                    default => '',
                };
            },
            $text
        );
    }

    private function throwApiException($response): never
    {
        $body = $response->body();

        $message = 'Error desconocido de Anthropic.';

        $decoded = json_decode(
            $body,
            true
        );

        if (
            is_array($decoded)
            && isset($decoded['error']['message'])
        ) {
            $message = $decoded['error']['message'];
        }

        Log::error('Anthropic API error', [
            'status' => $response->status(),
            'body' => $body,
        ]);

        throw new RuntimeException(
            'Anthropic API [' . $response->status() . ']: ' . $message
        );
    }
}