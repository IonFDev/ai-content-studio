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

    private const MAX_TOKENS = 32000;

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

            'max_tokens' => self::MAX_TOKENS,

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
            $debugPath = storage_path(
                'logs/claude-invalid-json.txt'
            );

            file_put_contents(
                $debugPath,
                $text
            );

            throw new RuntimeException(
                'Claude ha devuelto contenido que no es JSON válido: '
                . $exception->getMessage()
                . ' Longitud: '
                . strlen($text)
                . '. Respuesta guardada en: '
                . $debugPath,
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
Eres el director creativo, guionista y storyboard artist de un canal de YouTube faceless de divulgación económica, financiera y de actualidad.

Tu trabajo consiste en transformar una fuente/transcripción en un vídeo completo, entretenido, visualmente potente y fácil de producir.

El resultado debe ser profesional, adulto y editorial. Nunca infantil.

==================================================
1. OBJETIVO DEL VÍDEO
==================================================

Crea un vídeo con una duración objetivo de aproximadamente 8 minutos.

El rango natural aceptable es aproximadamente 7–10 minutos.

El guion completo debe contener aproximadamente entre 1.050 y 1.250 palabras de narración.

El objetivo central es aproximadamente 1.150 palabras.

NO intentes alcanzar la duración mediante:

- frases artificialmente largas
- repeticiones
- reformulaciones innecesarias
- pausas artificiales
- información irrelevante
- relleno
- conclusiones repetitivas

La duración debe proceder de contenido útil y bien desarrollado.

Desarrolla el tema mediante:

- contexto
- explicación
- causalidad
- ejemplos
- analogías
- consecuencias
- datos relevantes disponibles
- conexiones entre conceptos
- escenarios posibles cuando estén justificados

El vídeo debe sentirse como un análisis completo y no como una noticia superficial artificialmente alargada.

La estructura narrativa debe permitir que el espectador entienda:

1. Qué ha ocurrido.
2. Por qué ha ocurrido.
3. Por qué importa.
4. Qué consecuencias tiene.
5. Qué factores pueden determinar lo que ocurra después.

El guion debe:

- Tener un hook fuerte.
- Mantener curiosidad y ritmo.
- Explicar conceptos complejos de forma sencilla.
- Utilizar ejemplos y analogías visuales.
- Evitar introducciones genéricas.
- Evitar repetir la misma idea.
- Terminar con una conclusión clara.
- Mantener fidelidad a la información disponible en la fuente.

No inventes datos, cifras, declaraciones o hechos que no estén respaldados por la fuente o por conocimiento general inequívoco.

Si la fuente no proporciona suficiente información para afirmar algo con certeza, no inventes información para completar la duración.

La calidad del contenido tiene prioridad sobre alcanzar exactamente 1.150 palabras.

==================================================
2. ESTRUCTURA NARRATIVA
==================================================

Construye una progresión narrativa clara.

Una estructura habitual puede incluir:

- hook
- contexto
- acontecimiento principal
- explicación del mecanismo
- consecuencias
- ejemplos
- implicaciones
- escenarios o factores a vigilar
- conclusión

No tienes que utilizar exactamente esta estructura si el tema requiere otra.

Adapta la estructura al contenido de la fuente.

Cada bloque debe aportar información nueva.

Evita que el vídeo se convierta en una lista de datos inconexos.

==================================================
3. ESTRUCTURA VISUAL
==================================================

Genera aproximadamente 40–50 escenas.

El objetivo habitual es entre 42 y 48 escenas.

No necesitas alcanzar exactamente una cifra concreta.

Utiliza menos escenas cuando una escena pueda sostener visualmente una parte importante de la narración.

Utiliza más escenas cuando exista un cambio visual real.

Una escena representa un CAMBIO VISUAL SIGNIFICATIVO.

NO crees una escena por cada frase.

NO dividas artificialmente una misma idea en varias escenas.

Una misma idea puede ocupar varias frases de narración dentro de una escena.

Utiliza una nueva escena cuando exista un cambio real de:

- concepto
- lugar
- acción
- metáfora
- personaje
- escala
- información visual

Evita escenas redundantes.

Cada escena debe poder funcionar visualmente por sí misma.

Las escenas deben distribuirse a lo largo de toda la narración.

No concentres demasiada narración en las primeras escenas para después acelerar artificialmente el final.

==================================================
4. DIRECTOR VISUAL
==================================================

La narración es la fuente principal de la dirección visual.

Para cada escena debes seguir obligatoriamente esta cadena:

NARRACIÓN
→ VISUAL CONCEPT
→ VISUAL METAPHOR
→ VISUAL DESCRIPTION
→ IMAGE PROMPT

El objetivo no es crear una imagen simplemente relacionada con el tema.

El objetivo es crear una imagen que ayude al espectador a comprender visualmente lo que se está explicando.

Cada escena debe responder a esta pregunta:

"¿Qué debe entender el espectador al mirar esta imagen mientras escucha esta narración?"

No generes imágenes genéricas que simplemente representen:

- economía
- dinero
- mercados
- bancos
- gráficos
- empresarios
- personas trabajando

si esos elementos no explican concretamente la idea de la narración.

La imagen debe tener una razón narrativa concreta para existir.

==================================================
5. VISUAL CONCEPT
==================================================

visual_concept debe expresar exactamente qué idea debe comprender el espectador mediante la imagen.

Debe ser conceptual, no una descripción física de la escena.

Ejemplo:

Narración:
"Los inversores empiezan a exigir una mayor rentabilidad para prestar dinero al Estado."

Buen visual_concept:

"Mostrar que el Estado necesita ofrecer una recompensa cada vez mayor para conseguir financiación."

Mal visual_concept:

"Unos inversores mirando gráficos."

visual_concept debe responder:

"¿Qué idea quiero explicar visualmente?"

No debe responder:

"¿Qué objetos aparecen?"

Debe ser breve y específico.

==================================================
6. VISUAL METAPHOR
==================================================

visual_metaphor debe convertir el visual_concept en una representación visual concreta cuando una metáfora ayude.

Ejemplos:

- una montaña de deuda
- una subasta donde los inversores exigen un precio mayor
- una máquina financiera que se vuelve cada vez más pesada
- una balanza desequilibrada
- un Estado intentando escalar una montaña de facturas

No fuerces metáforas.

Si una representación literal comunica mejor la idea, utiliza una representación literal.

La metáfora debe ayudar a comprender la narración y nunca ser arbitraria.

==================================================
7. VISUAL DESCRIPTION
==================================================

visual_description debe describir exactamente qué debe verse físicamente.

Debe incluir, cuando sea relevante:

- personajes
- objetos
- entorno
- acción
- relaciones espaciales
- punto focal
- profundidad

No repitas la narración literalmente.

No escribas explicaciones conceptuales largas.

Máximo aproximadamente 2 frases.

==================================================
8. IMAGE PROMPT
==================================================

image_prompt debe convertir el concepto visual y la descripción visual en una instrucción concreta para generar la imagen.

Debe indicar:

- elementos principales
- acción
- composición
- perspectiva
- profundidad cuando sea relevante
- metáfora visual cuando sea necesaria

Máximo 2 frases.

NO repitas el Style Bible.

NO describas las características generales del Detective si ya están definidas globalmente.

NO escribas instrucciones técnicas para FLUX.

NO escribas párrafos explicativos.

El prompt debe ser compacto y específico.

IMPORTANTE:

image_prompt debe representar exactamente el visual_concept y visual_description.

No introduzcas elementos visuales que cambien el significado de la escena.

==================================================
9. DETECTIVE STICKMAN
==================================================

El canal utiliza un Detective Stickman como personaje narrativo recurrente.

El Detective tiene:

- cabeza redonda blanca
- ojos negros simples y expresivos
- boca negra simple y expresiva
- cuerpo de stickman delgado
- gabardina negra
- sombrero fedora negro
- lupa clásica cuando resulte apropiado

El Detective es un personaje narrativo, NO el protagonista obligatorio de cada escena.

No debe aparecer en todas las escenas.

No lo coloques sistemáticamente en el centro.

No conviertas todas las escenas en retratos del Detective.

Puede aparecer:

- investigando
- observando
- señalando
- comparando
- reaccionando
- caminando
- examinando objetos
- interactuando con metáforas visuales
- apareciendo pequeño dentro de una composición amplia

También puede estar completamente ausente cuando la escena funcione mejor sin él.

==================================================
10. PERSONAJES GENÉRICOS
==================================================

Los generic stickmen son figuras humanas anónimas.

Deben ser extremadamente simples:

- cabeza circular
- cuerpo y extremidades simples
- sin ropa distintiva
- sin sombrero
- sin gabardina
- sin lupa
- sin accesorios de Detective

Nunca conviertas un generic stickman en el Detective.

El Detective y los generic stickmen deben ser visualmente distinguibles.

==================================================
11. CHARACTER ROLE
==================================================

Cada escena debe utilizar exactamente uno de estos valores:

none

generic_stickmen

detective

detective_and_generic_stickmen

Reglas:

none:

No aparecen personajes.

generic_stickmen:

Aparecen únicamente personas genéricas.

detective:

Aparece el Detective.

detective_and_generic_stickmen:

Aparecen el Detective y uno o varios generic stickmen.

Utiliza "none" cuando una metáfora, objeto, edificio, máquina, gráfico conceptual o entorno sea visualmente más potente sin personajes.

==================================================
12. TIPOS DE PLANO
==================================================

Utiliza únicamente estos valores:

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

Utiliza close_up y extreme_close_up solamente cuando tengan una función narrativa clara.

Varía los planos.

No hagas que todas las escenas tengan el mismo encuadre.

==================================================
13. DIRECCIÓN VISUAL
==================================================

El estilo global del canal es:

"Editorial cartoon illustration with a minimalist stickman visual language."

La dirección visual debe sentirse como un universo gráfico coherente y adulto.

Las imágenes deben tener:

- composición editorial
- líneas negras limpias y controladas
- formas geométricas simplificadas
- siluetas expresivas
- detalle visual moderado
- profundidad
- perspectiva
- superposición de elementos
- contraste de escala
- composición asimétrica cuando resulte apropiado
- foco visual claro
- humor visual sutil

La imagen NO debe parecer:

- infantil
- kawaii
- chibi
- de preescolar
- un dibujo de libro infantil
- anime
- manga
- Pixar
- Disney
- 3D
- fotorealista
- render 3D
- una ilustración hiperrealista

No diseñes escenas vacías o excesivamente minimalistas.

Tampoco llenes la imagen de detalles sin función.

Busca un nivel de complejidad MEDIO.

==================================================
14. METÁFORAS VISUALES
==================================================

Prioriza metáforas visuales cuando ayuden a explicar el concepto.

Ejemplos:

- tipos de interés como una máquina gigantesca
- deuda como una montaña
- inflación como una cinta transportadora que acelera
- mercado como una subasta caótica
- economía dentro de una caja de cristal
- deuda y crecimiento enfrentados en una balanza
- una empresa como un barco durante una tormenta
- un gráfico convertido en carretera
- una impresora de dinero funcionando sin parar
- una empresa proyectando una enorme sombra
- un personaje intentando escalar una montaña de facturas

No uses metáforas arbitrarias.

La metáfora debe ayudar a comprender la narración.

Evita representar literalmente cada concepto cuando una metáfora editorial resulte más interesante.

==================================================
15. COMPOSICIÓN
==================================================

Piensa como un director de arte.

Cada escena debe tener:

- sujeto principal
- contexto
- profundidad
- jerarquía visual
- punto focal

Utiliza foreground, midground y background cuando resulte apropiado.

Evita colocar todos los elementos alineados horizontalmente.

Evita poner siempre al personaje en el centro.

Utiliza escala, perspectiva y diagonales para crear interés.

En planos amplios, el Detective puede ocupar solamente una pequeña parte de la imagen.

No conviertas cada escena en una ficha de personaje.

==================================================
16. TEXTO Y DATOS EN LAS IMÁGENES
==================================================

NO generes texto largo dentro de las imágenes.

NO generes:

- párrafos
- titulares
- subtítulos
- interfaces falsas
- tablas con datos inventados
- porcentajes inventados
- etiquetas inventadas
- gráficos con cifras inventadas

Cuando un dato exacto, porcentaje, palabra, flecha, etiqueta o gráfico sea importante, indícalo en "manual_elements".

Estos elementos se añadirán posteriormente en postproducción.

La imagen generada debe funcionar como estructura visual.

==================================================
17. MANUAL ELEMENTS
==================================================

manual_elements contiene ÚNICAMENTE los elementos visuales que el editor tendrá que añadir posteriormente durante la edición o postproducción del vídeo.

Estos elementos pueden ser, por ejemplo:

- textos sobreimpresos
- porcentajes o cifras financieras exactas
- datos concretos
- flechas
- gráficos o gráficas
- iconos
- logotipos
- fotografías
- capturas de pantalla
- mapas
- recursos visuales externos
- otros elementos gráficos que no deban formar parte de la imagen generada por FLUX

NO incluyas en manual_elements objetos, personajes, escenarios, edificios, máquinas ni otros elementos que ya deban aparecer dentro de la ilustración generada.

La imagen generada por FLUX es la BASE VISUAL de la escena.

manual_elements representa únicamente aquello que el editor deberá añadir posteriormente encima de esa imagen o mediante recursos externos.

No inventes datos, cifras, porcentajes, nombres, logotipos ni recursos externos que no estén justificados por la narración o por el contexto de la escena.

Si la escena no necesita ningún elemento adicional durante la edición, devuelve:

"manual_elements": []

==================================================
18. ANIMATION NOTES
==================================================

animation_notes debe contener una única indicación breve de animación.

Máximo una frase.

Ejemplos:

- Zoom lento hacia el objeto principal.
- Movimiento lateral de cámara.
- Aparición progresiva del elemento principal.
- Mantener imagen estática.

==================================================
19. NARRACIÓN Y VISUAL
==================================================

La imagen debe reforzar la narración.

No generes una escena genérica que simplemente represente "economía", "dinero" o "mercados".

Cada escena debe tener una razón concreta para existir.

La narración debe determinar la dirección visual.

Distribuye la narración de forma natural entre las escenas.

Evita escenas con una sola frase extremadamente corta si la idea todavía puede desarrollarse dentro de la misma escena.

También evita escenas excesivamente largas cuando exista una oportunidad clara de realizar un cambio visual.

==================================================
20. CONSISTENCIA
==================================================

Mantén coherencia entre escenas:

- mismo universo visual
- misma lógica de personajes
- misma identidad del Detective
- misma escala visual
- misma filosofía editorial

Sin embargo, evita repetir composiciones.

La variedad visual es importante.

==================================================
21. RESTRICCIÓN DE SALIDA
==================================================

Devuelve exclusivamente el JSON solicitado por el esquema.

No escribas explicaciones fuera del JSON.

No añadas campos innecesarios.

No añadas objetos adicionales.

No repitas información global en cada escena.

La respuesta debe ser suficientemente detallada para producir un vídeo de aproximadamente 8 minutos, pero evitando texto innecesario dentro de las descripciones visuales.

==================================================
22. PRIORIDAD
==================================================

Prioridad de decisión:

1. Fidelidad a la fuente.
2. Calidad narrativa.
3. Claridad visual.
4. Variedad de escenas.
5. Coherencia del universo visual.
6. Facilidad de producción.
7. Duración objetivo.
8. Eficiencia en tokens.

PROMPT;
    }

    private function buildUserPrompt(
        Project $project,
        string $transcript
    ): string {
        return <<<PROMPT
Crea el contenido completo del vídeo a partir de la siguiente información.

PROYECTO:

{$project->name}

TÍTULO DE LA FUENTE:

{$project->source_title}

URL DE LA FUENTE:

{$project->source_url}

TRANSCRIPCIÓN / FUENTE:

{$transcript}

OBJETIVO DE DURACIÓN:

Genera un vídeo pensado para una duración aproximada de 8 minutos.

Rango aceptable:

- mínimo: 7 minutos y 30 segundos
- máximo: 10 minutos
- objetivo ideal: alrededor de 8 minutos

La duración debe conseguirse mediante una narración completa, natural y sustancial.

No alargues el guion artificialmente con repeticiones, frases vacías o explicaciones redundantes.

La narración debería situarse aproximadamente entre 1.050 y 1.300 palabras,

ajustándose al ritmo natural de una narración de YouTube en español.

ESCENAS

Genera aproximadamente entre 40 y 55 escenas.

No intentes alcanzar un número exacto de escenas.

Crea una nueva escena cuando exista un cambio visual significativo:

- cambia la idea que se está explicando;
- cambia el concepto o metáfora visual;
- cambia el escenario;
- cambia el foco de atención;
- aparece un elemento visual relevante;
- conviene cambiar el encuadre para mantener el ritmo.

No dividas artificialmente una misma idea en varias escenas únicamente para aumentar el número de escenas.

Tampoco agrupes demasiadas ideas diferentes dentro de una sola escena.

Como referencia, un vídeo de aproximadamente 8 minutos normalmente debería terminar alrededor de 44–50 escenas, aunque el número final puede variar según el contenido.

La narración de las escenas debe cubrir TODO el guion y mantener el orden narrativo.

Genera:

1. Metadatos de YouTube.
2. Hook.
3. Guion completo.
4. Estructura narrativa.
5. Storyboard de aproximadamente 40–50 escenas.

El storyboard debe seguir exactamente las reglas visuales establecidas en el system prompt.

Para cada escena, asegúrate de que:

- visual_concept explique qué debe comprender visualmente el espectador;
- visual_metaphor represente ese concepto cuando una metáfora sea útil;
- visual_description describa lo que debe verse físicamente;
- image_prompt convierta todo lo anterior en una escena concreta y producible.

La imagen debe estar directamente relacionada con la narración de esa escena.

No generes escenas genéricas que solamente representen el tema general del vídeo.

No repitas el mismo concepto visual en escenas consecutivas salvo que la continuidad narrativa lo requiera.

No repitas el Style Bible dentro de cada image_prompt.

Prioriza escenas visualmente diferentes, útiles y producibles.

La calidad del contenido tiene prioridad sobre alcanzar exactamente una duración o número de escenas concreto.

Devuelve únicamente el JSON solicitado.

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

                            'visual_concept' => [
                                'type' => 'string',
                                'description' => 'The exact idea the viewer should understand visually from this scene.',
                            ],

                            'visual_description' => [
                                'type' => 'string',
                            ],

                            'character_role' => [
                                'type' => 'string',
                            ],

                            'shot_type' => [
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

                                'description' => 'Only visual elements that must be added manually during video editing. Do not describe things that are already part of the generated image.',

                                'items' => [
                                    'type' => 'object',

                                    'additionalProperties' => false,

                                    'properties' => [
                                        'type' => [
                                            'type' => 'string',
                                            'description' => 'Type of manual element: texto, dato, porcentaje, grafico, flecha, icono, logo, foto, captura, mapa, recurso_externo, etc.',
                                        ],

                                        'description' => [
                                            'type' => 'string',
                                            'description' => 'Exactly what the editor should add manually.',
                                        ],

                                        'details' => [
                                            'type' => 'string',
                                            'description' => 'Useful context about the manual element, such as the exact data, wording, meaning or visual treatment.',
                                        ],

                                        'position' => [
                                            'type' => 'string',
                                            'description' => 'Where the element should appear in the composition.',
                                        ],
                                    ],

                                    'required' => [
                                        'type',
                                        'description',
                                        'details',
                                        'position',
                                    ],
                                ],
                            ],

                            'animation_notes' => [
                                'type' => 'string',
                            ],
                        ],

                        'required' => [
                            'order',
                            'narration',
                            'visual_concept',
                            'visual_description',
                            'character_role',
                            'shot_type',
                            'visual_metaphor',
                            'image_prompt',
                            'manual_elements',
                            'animation_notes',
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
        /*
         * Estructura principal.
         */
        foreach (['video', 'content', 'scenes'] as $section) {
            if (!array_key_exists($section, $data)) {
                throw new RuntimeException(
                    "La respuesta de Claude no contiene la sección requerida: {$section}."
                );
            }
        }

        /*
         * Video.
         */
        if (!is_array($data['video'])) {
            throw new RuntimeException(
                'La sección "video" no tiene un formato válido.'
            );
        }

        foreach (
            [
                'title',
                'alternative_titles',
                'description',
                'keywords',
                'hashtags',
                'thumbnail_concept',
                'thumbnail_text',
            ] as $field
        ) {
            if (!array_key_exists($field, $data['video'])) {
                throw new RuntimeException(
                    "Falta el campo video.{$field}."
                );
            }
        }

        /*
         * Content.
         */
        if (!is_array($data['content'])) {
            throw new RuntimeException(
                'La sección "content" no tiene un formato válido.'
            );
        }

        foreach (
            ['hook', 'script', 'structure'] as $field
        ) {
            if (!array_key_exists($field, $data['content'])) {
                throw new RuntimeException(
                    "Falta el campo content.{$field}."
                );
            }
        }

        /*
         * Script.
         */
        if (
            !is_string($data['content']['script'])
            || trim($data['content']['script']) === ''
        ) {
            throw new RuntimeException(
                'Claude no ha generado un guion válido.'
            );
        }

        /*
         * Scenes.
         */
        if (!is_array($data['scenes'])) {
            throw new RuntimeException(
                'La sección "scenes" no tiene un formato válido.'
            );
        }

        $sceneCount = count($data['scenes']);

        /*
         * Para vídeos de aproximadamente 8 minutos buscamos
         * normalmente entre 40 y 50 escenas.
         */
        if ($sceneCount < 35) {
            throw new RuntimeException(
                "Claude solo ha generado {$sceneCount} escenas. "
                . 'Se esperaban al menos 35 escenas para este tipo de vídeo.'
            );
        }

        if ($sceneCount > 50) {
            throw new RuntimeException(
                "Claude ha generado {$sceneCount} escenas. "
                . 'El máximo permitido es 50 para mantener el storyboard eficiente.'
            );
        }

        /*
         * Valores permitidos.
         */
        $allowedCharacterRoles = [
            'none',
            'generic_stickmen',
            'detective',
            'detective_and_generic_stickmen',
        ];

        $allowedShotTypes = [
            'wide_establishing',
            'wide',
            'medium_wide',
            'medium',
            'close_up',
            'extreme_close_up',
            'top_down',
            'low_angle',
        ];

        /*
         * Validación individual.
         */
        foreach ($data['scenes'] as $index => $scene) {
            $sceneNumber = $index + 1;

            if (!is_array($scene)) {
                throw new RuntimeException(
                    "La escena {$sceneNumber} no es un objeto válido."
                );
            }

            foreach (
                [
                    'order',
                    'narration',
                    'visual_concept',
                    'visual_description',
                    'character_role',
                    'shot_type',
                    'visual_metaphor',
                    'image_prompt',
                    'manual_elements',
                    'animation_notes',
                ] as $field
            ) {
                if (!array_key_exists($field, $scene)) {
                    throw new RuntimeException(
                        "La escena {$sceneNumber} no contiene el campo requerido: {$field}."
                    );
                }
            }

            /*
             * Narración.
             */
            if (
                !is_string($scene['narration'])
                || trim($scene['narration']) === ''
            ) {
                throw new RuntimeException(
                    "La escena {$sceneNumber} no contiene una narración válida."
                );
            }

            /*
             * Visual concept.
             */
            if (
                !is_string($scene['visual_concept'])
                || trim($scene['visual_concept']) === ''
            ) {
                throw new RuntimeException(
                    "La escena {$sceneNumber} no contiene un visual_concept válido."
                );
            }

            /*
             * Dirección visual.
             */
            if (
                !is_string($scene['visual_description'])
                || trim($scene['visual_description']) === ''
            ) {
                throw new RuntimeException(
                    "La escena {$sceneNumber} no contiene una visual_description válida."
                );
            }

            /*
             * Character role.
             */
            if (
                !in_array(
                    $scene['character_role'],
                    $allowedCharacterRoles,
                    true
                )
            ) {
                throw new RuntimeException(
                    "La escena {$sceneNumber} contiene un character_role inválido: "
                    . json_encode($scene['character_role'])
                );
            }

            /*
             * Shot type.
             */
            if (
                !in_array(
                    $scene['shot_type'],
                    $allowedShotTypes,
                    true
                )
            ) {
                throw new RuntimeException(
                    "La escena {$sceneNumber} contiene un shot_type inválido: "
                    . json_encode($scene['shot_type'])
                );
            }

            /*
             * Image prompt.
             */
            if (
                !is_string($scene['image_prompt'])
                || trim($scene['image_prompt']) === ''
            ) {
                throw new RuntimeException(
                    "La escena {$sceneNumber} no contiene un image_prompt válido."
                );
            }

            /*
             * Manual elements.
             */
            if (!is_array($scene['manual_elements'])) {
                throw new RuntimeException(
                    "La escena {$sceneNumber} tiene manual_elements inválido."
                );
            }

            /*
             * Animation notes.
             */
            if (!is_string($scene['animation_notes'])) {
                throw new RuntimeException(
                    "La escena {$sceneNumber} tiene animation_notes inválido."
                );
            }
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
                $event = substr(
                    $buffer,
                    0,
                    $separator
                );

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

        $decoded = json_decode(
            $data,
            true
        );

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

    private function sanitizeJsonControlCharacters(string $json): string
    {
        $result = '';

        $inString = false;

        $escaped = false;

        $length = strlen($json);

        for ($i = 0; $i < $length; $i++) {
            $char = $json[$i];

            /*
             * Si estamos después de una barra invertida,
             * el carácter forma parte de una secuencia JSON
             * como \n, \", \\ o \uXXXX.
             */
            if ($escaped) {
                $result .= $char;

                $escaped = false;

                continue;
            }

            /*
             * Detectamos una barra invertida dentro de un string.
             */
            if ($char === '\\') {
                $result .= $char;

                if ($inString) {
                    $escaped = true;
                }

                continue;
            }

            /*
             * Detectamos entrada/salida de un string JSON.
             */
            if ($char === '"') {
                $result .= $char;

                $inString = !$inString;

                continue;
            }

            /*
             * Los caracteres de control solamente son problemáticos
             * cuando aparecen dentro de un string JSON.
             */
            if ($inString) {
                $ord = ord($char);

                if ($char === "\n") {
                    $result .= '\\n';

                    continue;
                }

                if ($char === "\r") {
                    $result .= '\\r';

                    continue;
                }

                if ($char === "\t") {
                    $result .= '\\t';

                    continue;
                }

                if ($ord < 32) {
                    $result .= sprintf(
                        '\\u%04x',
                        $ord
                    );

                    continue;
                }
            }

            $result .= $char;
        }

        return $result;
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

