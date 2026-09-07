<?php

namespace App\Services\AI;

use App\Contracts\AI\AIProvider;
use App\Models\Project;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ClaudeProvider implements AIProvider
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';

    public function generateContent(
        Project $project,
        string $transcript
    ): array {
        $apiKey = config('youtube_studio.anthropic.api_key');
        $model = config('youtube_studio.anthropic.model');
        $timeout = (int) config(
            'youtube_studio.anthropic.timeout',
            300
        );

        if (blank($apiKey)) {
            throw new RuntimeException(
                'ANTHROPIC_API_KEY no está configurada.'
            );
        }

        $transcript = trim($transcript);

        if ($transcript === '') {
            throw new RuntimeException(
                'La transcripción está vacía.'
            );
        }

        $response = Http::timeout($timeout)
            ->acceptJson()
            ->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
            ])
            ->post(self::API_URL, [
                'model' => $model,
                'max_tokens' => 20000,

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
            ]);

        if ($response->failed()) {
            $this->throwApiException($response);
        }

        $stopReason = $response->json('stop_reason');

        if ($stopReason !== 'end_turn') {
            throw new RuntimeException(
                'Claude no terminó correctamente la generación. '
                . 'Motivo: ' . ($stopReason ?? 'desconocido')
            );
        }

        $content = $response->json('content');

        if (!is_array($content)) {
            throw new RuntimeException(
                'Claude no ha devuelto contenido válido.'
            );
        }

        $text = collect($content)
            ->where('type', 'text')
            ->pluck('text')
            ->implode("\n");

        $text = trim($text);

        if ($text === '') {
            throw new RuntimeException(
                'Claude no ha devuelto ningún contenido.'
            );
        }

        $data = json_decode(
            $text,
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        if (!is_array($data)) {
            throw new RuntimeException(
                'La respuesta de Claude no es un objeto JSON válido.'
            );
        }

        $this->validateResponse($data);

        return $data;
    }

    private function buildSystemPrompt(): string
    {
        return <<<'PROMPT'
Eres el guionista principal de un canal de YouTube faceless sobre
economía, finanzas y actualidad.

Tu trabajo es transformar una fuente o tema de actualidad en un vídeo
ORIGINAL, entretenido, dinámico y visual.

El protagonista visual recurrente del canal es un único personaje:

UN DETECTIVE STICKMAN.

==================================================
PERSONAJE
==================================================

Características visuales:

- Stickman minimalista.
- Cuerpo y extremidades negras.
- Cabeza redonda blanca.
- Ojos negros sencillos y expresivos.
- Boca muy simple y expresiva.
- Gabardina negra de detective.
- Sombrero fedora negro.
- Lupa clásica.
- Proporciones delgadas y minimalistas.
- Debe ser reconocible como el mismo personaje en todas las escenas.

El detective es el único personaje recurrente del canal.

Su diseño visual será proporcionado posteriormente mediante una imagen
de referencia maestra al generador de imágenes.

NO reconstruyas ni rediseñes el personaje en cada prompt.

==================================================
OBJETIVO
==================================================

El vídeo debe explicar un tema económico, financiero o de actualidad de
forma que una persona sin conocimientos especializados quiera seguir
viendo hasta el final.

NO escribas como un profesor universitario.

NO escribas como un periódico.

NO escribas como un informe financiero.

NO utilices un tono excesivamente formal.

El tono debe ser:

- entretenido
- inteligente
- curioso
- ligeramente irreverente
- gracioso cuando tenga sentido
- fácil de entender
- dinámico
- con sensación de historia

El humor debe hacer que los conceptos complejos sean más fáciles de
recordar.

La información debe seguir siendo rigurosa.

El humor nunca debe inventar datos ni distorsionar hechos importantes.

==================================================
ORIGINALIDAD
==================================================

La fuente proporcionada es material de investigación.

NO escribas una paráfrasis de la fuente.

NO traduzcas la fuente.

NO reformules la fuente frase por frase.

NO mantengas necesariamente el mismo orden de argumentos.

NO copies expresiones características de la fuente.

NO reproduzcas su estructura narrativa.

Extrae los hechos relevantes y construye una narrativa completamente
original para nuestro canal.

La introducción, estructura, transiciones, ejemplos, explicaciones,
humor y conclusión deben estar redactados desde cero.

==================================================
PERSONALIDAD DEL DETECTIVE
==================================================

El detective funciona como un investigador que intenta descubrir qué
está ocurriendo realmente detrás de las noticias económicas.

Puede:

- investigar
- sospechar
- descubrir pistas
- sorprenderse
- equivocarse inicialmente
- conectar acontecimientos
- señalar contradicciones
- reaccionar ante situaciones absurdas

No es necesario mencionarlo literalmente en la narración.

Su presencia debe surgir principalmente mediante las imágenes.

==================================================
ESTRUCTURA NARRATIVA
==================================================

Comienza con un HOOK.

Evita comenzar con:

"En este vídeo vamos a hablar de..."

"Hoy vamos a explicar..."

"En este vídeo veremos..."

El hook debe crear inmediatamente una pregunta, contradicción, sorpresa
o situación extraña.

Introduce la información progresivamente.

No reveles toda la explicación al principio.

Utiliza preguntas abiertas, pistas y pequeñas revelaciones.

Cada sección debe hacer que el espectador quiera conocer la siguiente.

==================================================
RETENCIÓN
==================================================

Utiliza transiciones narrativas como:

"Pero aquí aparece el primer problema."

"Y esto nos lleva a la parte realmente interesante."

"Porque si miramos este dato, la historia cambia."

"Y todavía no hemos llegado a lo más extraño."

"Pero hay una pieza que falta."

No copies literalmente estas frases de forma sistemática.

Úsalas únicamente como referencia de ritmo.

Evita párrafos largos sin cambios narrativos.

==================================================
HUMOR
==================================================

El humor debe ser natural y estar relacionado con lo explicado.

Puede utilizarse:

- ironía
- exageración
- comparaciones absurdas
- situaciones cotidianas
- reacciones del detective
- contradicciones
- pequeños comentarios sarcásticos

NO conviertas el vídeo en una comedia.

Prioridad:

1. información
2. narrativa
3. claridad
4. humor

==================================================
LENGUAJE
==================================================

Escribe el guion en español natural.

Utiliza frases relativamente cortas.

Evita terminología financiera innecesaria.

Cuando un concepto técnico sea imprescindible, explícalo
inmediatamente mediante una comparación sencilla.

El espectador debe poder seguir el vídeo aunque no tenga conocimientos
de economía.

==================================================
RITMO
==================================================

El vídeo debe sentirse dinámico aunque el tema sea complejo.

Alterna:

- explicación
- dato
- ejemplo
- reacción
- pregunta
- consecuencia
- nueva pista

Evita mantener demasiado tiempo el mismo tipo de explicación.

Debe existir variedad narrativa y visual durante todo el vídeo.

==================================================
FINAL
==================================================

El final debe:

- responder la pregunta principal
- explicar qué significa realmente lo ocurrido
- plantear qué podría suceder después
- dejar una última idea interesante

Cuando sea apropiado puede terminar con una pregunta abierta.

No hagas simplemente una lista de conclusiones.

==================================================
LONGITUD
==================================================

Genera un guion adecuado para aproximadamente 8-10 minutos.

Referencia:

1.100-1.500 palabras.

Prioriza la calidad narrativa sobre alcanzar exactamente una cantidad
determinada de palabras.

==================================================
STORYBOARD
==================================================

Después de escribir el guion, divídelo en escenas visuales.

Referencia:

40-60 escenas para un vídeo de 8-10 minutos.

NO dividas artificialmente cada X segundos.

Cada escena debe representar una idea visual concreta.

Una escena puede durar más o menos dependiendo de la importancia de
la información.

No generes dos escenas consecutivas esencialmente iguales salvo que
exista una razón narrativa clara.

==================================================
REGLA VISUAL PRINCIPAL
==================================================

Las imágenes deben tener un estilo extremadamente reconocible.

El lenguaje visual principal es:

STICKMAN + FONDO BLANCO + ELEMENTOS SIMPLES.

La mayoría de las escenas deben utilizar:

- fondo blanco limpio
- detective stickman negro
- pocos elementos
- objetos grandes
- composición limpia
- alto contraste
- mucho espacio vacío
- una idea visual clara

Evita escenas cinematográficas realistas salvo que una escena concreta
lo necesite.

NO convertir al personaje en:

- humano realista
- personaje 3D
- anime
- cartoon complejo
- superhéroe
- personaje detallado

Debe seguir siendo un stickman minimalista.

==================================================
VARIACIÓN VISUAL
==================================================

Aunque el fondo blanco sea la norma, pueden utilizarse:

- gráficos
- mapas
- edificios
- banderas
- monedas
- billetes
- periódicos
- pantallas
- flechas
- documentos
- símbolos
- objetos relacionados con el tema

Aproximadamente el 70-90% de las escenas deberían mantener:

STICKMAN + FONDO BLANCO + ELEMENTOS SIMPLES.

No es una regla matemática.

La claridad narrativa tiene prioridad sobre la uniformidad absoluta.

==================================================
CONSISTENCIA
==================================================

TODAS las escenas representan al MISMO detective.

No cambies:

- ropa
- sombrero
- proporciones
- estilo
- color
- forma de la cabeza

La referencia maestra del personaje será enviada posteriormente al
generador de imágenes.

NO intentes reconstruir la referencia dentro de cada prompt.

==================================================
PROMPTS DE IMAGEN
==================================================

Cada image_prompt debe estar escrito en INGLÉS.

Debe poder utilizarse directamente como prompt de un generador de
imágenes.

Cada prompt debe describir:

1. Qué está haciendo el detective.
2. Qué objeto o elemento aparece.
3. La composición.
4. La emoción o actitud.
5. El concepto económico que representa.
6. El estilo visual.
7. El fondo.

No utilices instrucciones ambiguas como:

"Show the economy."

Eso no es suficientemente visual.

Los prompts deben ser concretos y visuales.

La referencia del personaje será proporcionada al generador por separado.

==================================================
COMPOSICIÓN
==================================================

No hagas que el detective sea el elemento principal de absolutamente
todas las escenas.

Utiliza diferentes composiciones:

- detective + objeto
- detective + gráfico
- detective pequeño frente a un objeto enorme
- objeto económico como protagonista
- gráfico o diagrama
- detective reaccionando
- documentos
- monedas
- edificios
- mapas
- pantallas
- metáforas visuales

El detective funciona como hilo conductor visual.

Las imágenes deben explicar la información, no simplemente decorar.

==================================================
RELACIÓN ENTRE NARRACIÓN E IMAGEN
==================================================

La imagen debe representar o reforzar exactamente la idea explicada
en ese momento.

Cada escena debe responder:

"¿Por qué esta imagen ayuda a entender esta frase?"

Si no existe una respuesta clara, cambia la imagen.

==================================================
DATOS Y RIGOR
==================================================

NO inventes:

- estadísticas
- fechas
- cifras
- declaraciones
- acontecimientos
- nombres
- citas

Si la fuente no permite confirmar un dato, no lo presentes como hecho.

Distingue entre:

- hecho
- interpretación
- posibilidad
- predicción

Cuando hables del futuro utiliza lenguaje apropiado:

"podría"
"es posible"
"una de las posibilidades"
"los analistas esperan"

No presentes predicciones como certezas.

==================================================
CONSISTENCIA ENTRE SCRIPT Y ESCENAS
==================================================

El campo content.script contiene el guion completo.

La concatenación de todos los campos scenes[].narration, respetando
el orden de las escenas, debe reproducir exactamente el guion completo.

NO resumas el guion al dividirlo en escenas.

NO elimines frases.

NO añadas frases que no existan en el script.

NO cambies palabras entre script y narration.

Divide el guion en puntos naturales de transición.

==================================================
RESULTADO
==================================================

Devuelve exclusivamente el objeto JSON solicitado por el esquema
estructurado de la API.

No añadas explicaciones antes ni después.

No utilices Markdown.

No utilices bloques de código.

No escribas texto fuera del JSON.

Antes de finalizar, verifica:

- que el JSON cumple el esquema
- que el guion tiene aproximadamente 1.100-1.500 palabras
- que existen aproximadamente 40-60 escenas
- que todas las escenas tienen narración
- que todas las escenas tienen visual
- que todas las escenas tienen image_prompt
- que image_prompt está en inglés
- que la narración de las escenas coincide con el script
- que no existen datos inventados
- que el detective mantiene consistencia visual
PROMPT;
    }

    private function buildUserPrompt(
        Project $project,
        string $transcript
    ): string {
        return <<<PROMPT
<project>
    <name>{$project->name}</name>
    <source_title>{$project->source_title}</source_title>
    <source_url>{$project->source_url}</source_url>
</project>

<source_transcript>
{$transcript}
</source_transcript>

<task>
Transforma la fuente anterior en un vídeo original siguiendo
estrictamente todas las instrucciones editoriales y visuales del
system prompt.

La transcripción es la fuente factual principal.

No inventes información que no pueda sustentarse en ella.
</task>
PROMPT;
    }

    private function getOutputSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => [
                'video',
                'content',
                'scenes',
                'production',
                'metadata',
            ],
            'properties' => [
                'video' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => [
                        'title',
                        'alternative_titles',
                        'description',
                        'keywords',
                        'hashtags',
                        'thumbnail',
                        'estimated_duration_seconds',
                        'estimated_word_count',
                    ],
                    'properties' => [
                        'title' => [
                            'type' => 'string',
                        ],
                        'alternative_titles' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'string',
                            ],
                            'minItems' => 3,
                            'maxItems' => 3,
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
                        'thumbnail' => [
                            'type' => 'object',
                            'additionalProperties' => false,
                            'required' => [
                                'concept',
                                'text',
                            ],
                            'properties' => [
                                'concept' => [
                                    'type' => 'string',
                                ],
                                'text' => [
                                    'type' => 'string',
                                ],
                            ],
                        ],
                        'estimated_duration_seconds' => [
                            'type' => 'integer',
                        ],
                        'estimated_word_count' => [
                            'type' => 'integer',
                        ],
                    ],
                ],

                'content' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => [
                        'hook',
                        'script',
                        'structure',
                    ],
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
                                'type' => 'object',
                                'additionalProperties' => false,
                                'required' => [
                                    'section',
                                    'title',
                                    'purpose',
                                ],
                                'properties' => [
                                    'section' => [
                                        'type' => 'integer',
                                    ],
                                    'title' => [
                                        'type' => 'string',
                                    ],
                                    'purpose' => [
                                        'type' => 'string',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],

                'scenes' => [
                    'type' => 'array',
                    'minItems' => 1,
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => [
                            'id',
                            'order',
                            'narration',
                            'visual',
                            'image_prompt',
                        ],
                        'properties' => [
                            'id' => [
                                'type' => 'integer',
                            ],
                            'order' => [
                                'type' => 'integer',
                            ],
                            'narration' => [
                                'type' => 'string',
                            ],
                            'visual' => [
                                'type' => 'object',
                                'additionalProperties' => false,
                                'required' => [
                                    'description',
                                    'background',
                                    'characters',
                                    'objects',
                                    'camera',
                                    'composition',
                                ],
                                'properties' => [
                                    'description' => [
                                        'type' => 'string',
                                    ],
                                    'background' => [
                                        'type' => 'string',
                                    ],
                                    'characters' => [
                                        'type' => 'array',
                                        'items' => [
                                            'type' => 'string',
                                        ],
                                    ],
                                    'objects' => [
                                        'type' => 'array',
                                        'items' => [
                                            'type' => 'string',
                                        ],
                                    ],
                                    'camera' => [
                                        'type' => 'string',
                                    ],
                                    'composition' => [
                                        'type' => 'string',
                                    ],
                                ],
                            ],
                            'image_prompt' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],

                'production' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => [
                        'visual_style',
                        'image_generation',
                    ],
                    'properties' => [
                        'visual_style' => [
                            'type' => 'object',
                            'additionalProperties' => false,
                            'required' => [
                                'primary_background',
                                'character',
                                'style',
                                'consistency_required',
                            ],
                            'properties' => [
                                'primary_background' => [
                                    'type' => 'string',
                                ],
                                'character' => [
                                    'type' => 'string',
                                ],
                                'style' => [
                                    'type' => 'string',
                                ],
                                'consistency_required' => [
                                    'type' => 'boolean',
                                ],
                            ],
                        ],
                        'image_generation' => [
                            'type' => 'object',
                            'additionalProperties' => false,
                            'required' => [
                                'aspect_ratio',
                                'use_character_reference',
                                'reference_assets',
                            ],
                            'properties' => [
                                'aspect_ratio' => [
                                    'type' => 'string',
                                ],
                                'use_character_reference' => [
                                    'type' => 'boolean',
                                ],
                                'reference_assets' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'string',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],

                'metadata' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => [
                        'topic',
                        'category',
                        'tone',
                        'target_audience',
                    ],
                    'properties' => [
                        'topic' => [
                            'type' => 'string',
                        ],
                        'category' => [
                            'type' => 'string',
                        ],
                        'tone' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'string',
                            ],
                        ],
                        'target_audience' => [
                            'type' => 'string',
                        ],
                    ],
                ],
            ],
        ];
    }

    private function validateResponse(array $data): void
    {
        foreach (
            [
                'video',
                'content',
                'scenes',
                'production',
                'metadata',
            ] as $section
        ) {
            if (!array_key_exists($section, $data)) {
                throw new RuntimeException(
                    "Claude no ha devuelto la sección '{$section}'."
                );
            }
        }

        if (
            !isset($data['scenes']) ||
            !is_array($data['scenes']) ||
            count($data['scenes']) < 1
        ) {
            throw new RuntimeException(
                'Claude no ha generado escenas.'
            );
        }

        foreach ($data['scenes'] as $index => $scene) {
            foreach (
                [
                    'id',
                    'order',
                    'narration',
                    'visual',
                    'image_prompt',
                ] as $field
            ) {
                if (!array_key_exists($field, $scene)) {
                    throw new RuntimeException(
                        "La escena {$index} no contiene '{$field}'."
                    );
                }
            }

            if (!is_array($scene['visual'])) {
                throw new RuntimeException(
                    "El bloque visual de la escena {$index} no es válido."
                );
            }
        }

        $script = trim((string) $data['content']['script']);

        if ($script === '') {
            throw new RuntimeException(
                'Claude ha generado un guion vacío.'
            );
        }

        $wordCount = str_word_count(
            strip_tags($script)
        );

        if ($wordCount < 700 || $wordCount > 2000) {
            throw new RuntimeException(
                "La longitud del guion ({$wordCount} palabras) "
                . 'está fuera del rango permitido.'
            );
        }
    }

    private function throwApiException($response): never
    {
        $message = $response->json('error.message');

        if (!is_string($message) || trim($message) === '') {
            $message = $response->body();
        }

        throw new RuntimeException(
            'Anthropic API [' . $response->status() . ']: '
            . trim($message)
        );
    }
}
