<?php

namespace App\Services\AI;

use App\Contracts\AI\AIProvider;
use App\Models\Project;
use Illuminate\Http\Client\ConnectionException;
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
            900
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

        $payload = [
            'model' => $model,

            /*
             * La respuesta puede ser grande:
             * guion + 40-60 escenas + prompts de imagen.
             */
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

            /*
             * Anthropic enviará la respuesta mediante SSE.
             */
            'stream' => true,
        ];

        try {
            $response = Http::withOptions([
                'stream' => true,
            ])
                ->timeout($timeout)
                ->withHeaders([
                    'x-api-key' => $apiKey,
                    'anthropic-version' => '2023-06-01',
                    'accept' => 'text/event-stream',
                    'content-type' => 'application/json',
                ])
                ->post(self::API_URL, $payload);
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'No se pudo establecer o mantener la conexión con '
                . 'Anthropic: ' . $exception->getMessage(),
                0,
                $exception
            );
        }

        if ($response->failed()) {
            $this->throwApiException($response);
        }

        /*
         * Leemos el stream SSE y reconstruimos el texto JSON.
         */
        $text = $this->consumeStream($response);

        if ($text === '') {
            throw new RuntimeException(
                'Claude no ha devuelto ningún contenido.'
            );
        }

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
                . "\n\nRespuesta recibida:\n"
                . mb_substr($text, 0, 2000),
                0,
                $exception
            );
        }

        if (!is_array($data)) {
            throw new RuntimeException(
                'La respuesta de Claude no es un objeto JSON válido.'
            );
        }

        $this->validateResponse($data);

        return $data;
    }

    /**
     * Consume la respuesta SSE de Anthropic.
     */
    private function consumeStream($response): string
    {
        $body = $response->toPsrResponse()->getBody();

        $text = '';
        $buffer = '';

        while (!$body->eof()) {
            $chunk = $body->read(8192);

            if ($chunk === '') {
                continue;
            }

            $buffer .= $chunk;

            /*
             * SSE puede utilizar LF o CRLF.
             */
            while (
                preg_match(
                    "/\r?\n\r?\n/",
                    $buffer,
                    $matches,
                    PREG_OFFSET_CAPTURE
                )
            ) {
                $separator = $matches[0][0];
                $position = $matches[0][1];

                $event = substr(
                    $buffer,
                    0,
                    $position
                );

                $buffer = substr(
                    $buffer,
                    $position + strlen($separator)
                );

                $delta = $this->parseSseEvent($event);

                if ($delta !== null) {
                    $text .= $delta;
                }
            }
        }

        /*
         * Procesamos un posible último evento incompleto.
         */
        if (trim($buffer) !== '') {
            $delta = $this->parseSseEvent($buffer);

            if ($delta !== null) {
                $text .= $delta;
            }
        }

        return trim($text);
    }

    /**
     * Procesa un evento SSE individual.
     */
    private function parseSseEvent(string $event): ?string
    {
        $event = trim($event);

        if ($event === '') {
            return null;
        }

        $eventType = null;
        $dataLines = [];

        foreach (preg_split('/\r?\n/', $event) as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if (str_starts_with($line, 'event:')) {
                $eventType = trim(
                    substr($line, strlen('event:'))
                );

                continue;
            }

            if (str_starts_with($line, 'data:')) {
                $dataLines[] = trim(
                    substr($line, strlen('data:'))
                );
            }
        }

        if ($dataLines === []) {
            return null;
        }

        $data = implode("\n", $dataLines);

        if ($data === '[DONE]') {
            return null;
        }

        try {
            $payload = json_decode(
                $data,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException) {
            return null;
        }

        /*
         * Error enviado dentro del stream.
         */
        if (($payload['type'] ?? null) === 'error') {
            $errorMessage = $payload['error']['message']
                ?? 'Error desconocido durante el streaming de Anthropic.';

            throw new RuntimeException(
                'Anthropic API streaming error: '
                . $errorMessage
            );
        }

        /*
         * Solo acumulamos deltas de texto.
         */
        if (
            ($payload['type'] ?? null) !== 'content_block_delta'
        ) {
            return null;
        }

        if (
            ($payload['delta']['type'] ?? null) !== 'text_delta'
        ) {
            return null;
        }

        return (string) ($payload['delta']['text'] ?? '');
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
IDIOMA DE SALIDA
==================================================

La fuente puede estar escrita en cualquier idioma.

El contenido FINAL del vídeo debe estar escrito íntegramente en ESPAÑOL
NATURAL.

Esto incluye:

- hook
- guion
- narraciones de escenas
- títulos
- títulos alternativos
- descripción
- estructura
- metadata textual
- concepto de miniatura
- texto de miniatura

NO mantengas el idioma original de la fuente.

NO traduzcas literalmente la fuente.

La fuente es material de investigación, no un texto que deba traducirse.

Los image_prompt deben estar SIEMPRE escritos en INGLÉS.

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
REGLAS VISUALES — MUY IMPORTANTES
==================================================

El lenguaje visual del canal debe ser extremadamente consistente entre
todas las escenas.

ESTILO BASE:

- Minimalist black stickman line art.
- Clean white background.
- High contrast.
- Simple shapes.
- Thin, clean black lines.
- Flat 2D illustration.
- Large amounts of white space.
- Visuals immediately understandable.
- Same illustrated universe throughout the entire video.

==================================================
COLORES
==================================================

La paleta visual por defecto debe ser estrictamente:

NEGRO + BLANCO + ESCALA DE GRISES.

El negro debe ser el color visual dominante.

El fondo debe ser blanco.

NO introduzcas colores arbitrariamente.

NO utilices:

- ilustraciones multicolor
- gradientes
- colores neón
- efectos luminosos
- degradados
- colores decorativos
- paletas coloridas

Rojo, azul, verde, amarillo u otros colores SOLO pueden aparecer cuando
representen información específica y sea realmente útil para comprender
el concepto.

Si el color no aporta información, NO lo utilices.

Nunca introduzcas color simplemente para hacer la imagen más atractiva.

==================================================
DETECTIVE STICKMAN
==================================================

El Detective Stickman es el protagonista visual recurrente del canal.

Cuando aparezca, debe mantenerse visualmente idéntico:

- cabeza redonda blanca
- ojos negros simples y expresivos
- boca negra simple y expresiva
- cuerpo y extremidades negras
- gabardina negra de detective
- sombrero fedora negro
- proporciones delgadas y minimalistas
- lupa clásica cuando sea apropiado

Nunca rediseñes al detective.

Nunca cambies su ropa.

Nunca cambies su sombrero.

Nunca cambies sus proporciones.

Nunca cambies la forma de su cabeza.

Nunca le des anatomía humana realista.

Nunca lo hagas musculoso.

Nunca lo conviertas en un personaje 3D.

Nunca lo conviertas en anime.

Nunca lo conviertas en un cartoon complejo.

Nunca lo conviertas en un superhéroe.

Nunca cambies su estilo visual.

Una imagen de referencia maestra será proporcionada separadamente al
generador de imágenes.

El image_prompt NO debe intentar reconstruir ni redefinir la apariencia
del detective.

La referencia proporcionada determina su apariencia exacta.

==================================================
PRESENCIA DEL DETECTIVE
==================================================

El detective NO tiene que aparecer en todas las escenas.

Debe aparecer cuando ayude a la narrativa visual:

- investigando
- descubriendo una pista
- observando un acontecimiento económico
- señalando un gráfico
- examinando un documento
- reaccionando ante algo absurdo
- siguiendo una pista
- comparando elementos
- interactuando con un objeto
- descubriendo una contradicción

También son válidas escenas sin detective cuando un concepto se explica
mejor mediante:

- un gráfico
- un mapa
- un edificio
- un documento
- un objeto
- un símbolo
- una metáfora visual
- una comparación

NO fuerces al detective dentro de una escena si eso hace que el visual
sea menos claro.

El detective es el hilo conductor visual, no una obligación mecánica.

==================================================
CLARIDAD VISUAL
==================================================

Cada escena debe comunicar UNA idea visual primaria.

La imagen debe reforzar directamente la narración.

Antes de crear una escena, identifica mentalmente:

"¿Cuál es exactamente la idea que esta escena debe explicar?"

Después construye la imagen alrededor de esa idea.

NO generes imágenes meramente decorativas.

NO añadas objetos sin función narrativa.

==================================================
METÁFORAS VISUALES
==================================================

Cuando un concepto económico sea abstracto, utiliza una metáfora física
simple si ayuda a entenderlo.

Ejemplos del tipo de razonamiento esperado:

- aumento de demanda de préstamos → varias personas pidiendo dinero
- deuda creciente → objeto cada vez más pesado
- cuello de botella → paso físico estrecho
- inflación → precios aumentando
- dependencia → una persona conectada o atada a otra
- riesgo → objeto inestable o situación precaria

Estos ejemplos muestran el tipo de razonamiento visual esperado.

NO los copies literalmente cuando no correspondan al tema.

==================================================
ELEMENTOS VISUALES
==================================================

Pueden utilizarse:

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
- lupas
- candados
- cadenas
- balanzas
- colas
- puertas
- puentes
- escaleras
- objetos cotidianos

Solo deben aparecer cuando ayuden a comunicar la idea.

Evita elementos financieros genéricos sin función narrativa.

Por ejemplo, NO añadas dinero flotando simplemente porque el vídeo habla
de economía.

==================================================
COMPOSICIÓN
==================================================

Cada escena debe tener una composición intencionada.

El visual debe indicar:

- dónde está el detective o sujeto principal
- dónde están los objetos importantes
- qué debe observar primero el espectador
- cuál es la jerarquía visual

No centres automáticamente todos los elementos.

Varía naturalmente entre:

- detective a la izquierda
- detective a la derecha
- sujeto centrado
- objeto enorme dominando la imagen
- detective pequeño junto a un objeto enorme
- primer plano de un objeto
- composición asimétrica
- comparación entre dos objetos
- diagrama simple
- escena amplia

No repitas esencialmente la misma composición en escenas consecutivas.

==================================================
CAMERA
==================================================

Utiliza únicamente encuadres apropiados para una ilustración 2D:

- close-up
- medium shot
- wide shot
- centered diagram
- side composition
- simple overhead-like composition cuando sea útil

No utilices lenguaje cinematográfico que implique una escena 3D
realista.

==================================================
FONDO
==================================================

El fondo principal debe ser blanco y limpio.

NO utilices:

- habitaciones detalladas
- paisajes realistas
- oficinas complejas
- escenarios fotográficos
- fondos texturizados
- ambientes elaborados
- iluminación cinematográfica

Solo añade un elemento ambiental cuando sea necesario para comprender
la escena.

==================================================
TEXTO DENTRO DE LAS IMÁGENES
==================================================

Evita texto dentro de las imágenes siempre que sea posible.

NO añadas:

- párrafos
- diálogos
- captions
- etiquetas largas
- titulares complejos
- tipografías decorativas

Si un número, porcentaje, símbolo monetario o palabra muy corta es
imprescindible para comunicar el concepto, puede utilizarse.

El texto visual debe ser extremadamente corto.

==================================================
IMAGE PROMPTS
==================================================

Cada image_prompt debe estar escrito en INGLÉS.

Debe poder utilizarse directamente como prompt de un generador de
imágenes.

Cada prompt debe describir:

1. El sujeto principal.
2. La acción.
3. El concepto económico.
4. Los objetos relevantes.
5. La composición.
6. La expresión o actitud cuando sea relevante.
7. El estilo visual.
8. El fondo.

El prompt debe describir la imagen final que debe existir.

NO escribas explicaciones.

NO menciones el storyboard.

NO menciones la narración.

NO escribas instrucciones como:

"make this scene better"

"create a nice image"

El prompt debe ser concreto y visual.

==================================================
CONSISTENCIA DE IMAGE PROMPTS
==================================================

Todos los image_prompt deben mantener el lenguaje visual del canal:

minimalist black stickman line art,
clean white background,
simple shapes,
high contrast,
flat 2D illustration,
generous white space.

No introduzcas aleatoriamente:

- photorealism
- 3D rendering
- anime
- Pixar-like style
- realistic cartoon style
- painterly style
- comic-book rendering
- complex textures
- realistic shadows
- cinematic realism

La referencia del personaje determina la apariencia exacta del detective.

==================================================
RELACIÓN NARRACIÓN → IMAGEN
==================================================

La imagen debe representar o reforzar EXACTAMENTE la idea explicada en
ese momento.

Cada escena debe responder:

"¿Por qué esta imagen ayuda a entender esta frase?"

Si no existe una respuesta clara, cambia la imagen.

Si la narración explica un cambio, muestra el cambio.

Si explica una comparación, muestra la comparación.

Si explica una causa y consecuencia, representa visualmente la relación.

Si introduce un misterio, crea una pista visual.

Si describe algo absurdo, la imagen puede exagerarlo.

==================================================
VARIACIÓN ENTRE ESCENAS
==================================================

Las escenas deben sentirse como partes de un mismo vídeo.

Mantén el mismo universo visual mientras varías:

- composición
- escala
- posición del detective
- objetos
- metáforas
- encuadre
- acciones

NO hagas simplemente:

detective → gráfico → detective → gráfico

La narrativa visual debe evolucionar junto con la narrativa verbal.

No generes dos escenas consecutivas esencialmente iguales.

==================================================
ELEMENTOS GENÉRICOS A EVITAR
==================================================

Evita imágenes que podrían utilizarse indistintamente para cualquier
vídeo financiero.

NO utilices por defecto:

- hombre de negocios
- edificio financiero genérico
- bolsa de valores genérica
- monedas flotando
- billetes flotando
- gráfico aleatorio
- flechas sin significado
- apretón de manos
- ordenador genérico
- banco genérico

Cada elemento debe tener una razón narrativa.

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
CONTROL DE CALIDAD FINAL
==================================================

Antes de devolver el JSON final, verifica mentalmente cada escena.

Comprueba:

- el visual representa directamente la narración
- existe una idea visual primaria clara
- el detective es consistente cuando aparece
- el fondo es limpio y principalmente blanco
- el negro es el color dominante
- no existen colores arbitrarios
- no existe estilo 3D
- no existe estilo anime
- no existe fotorealismo
- no existen fondos complejos innecesarios
- las escenas consecutivas no son esencialmente iguales
- image_prompt está escrito en inglés
- image_prompt describe una imagen concreta
- los prompts son utilizables directamente por un generador
- no existen elementos decorativos sin función
- no existen datos inventados
- la narración de las escenas coincide exactamente con content.script
- existen aproximadamente 40-60 escenas
- el guion tiene aproximadamente 1.100-1.500 palabras

==================================================
RESULTADO
==================================================

Devuelve exclusivamente el objeto JSON solicitado por el esquema
estructurado de la API.

No añadas explicaciones antes ni después.

No utilices Markdown.

No utilices bloques de código.

No escribas texto fuera del JSON.
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

La fuente puede estar en cualquier idioma, pero TODO el contenido
final del vídeo debe estar en español natural.

NO traduzcas ni reformules la fuente línea por línea.

Utilízala como material de investigación para crear una pieza
completamente original.

Los image_prompt deben estar en inglés.

No inventes información que no pueda sustentarse en la fuente.
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
            if (!is_array($scene)) {
                throw new RuntimeException(
                    "La escena {$index} no es un objeto válido."
                );
            }

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

        if (
            !isset($data['content']['script']) ||
            !is_string($data['content']['script'])
        ) {
            throw new RuntimeException(
                'Claude no ha generado un guion válido.'
            );
        }

        $script = trim($data['content']['script']);

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