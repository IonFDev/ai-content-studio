<?php

namespace App\Services\Images;

use App\Contracts\Images\ImageProvider;
use App\Models\Scene;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use app\Services\Images\FluxPromptSanitizer;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class FluxProvider implements ImageProvider
{   
    public function __construct(
        private readonly FluxPromptSanitizer $promptSanitizer
    ) {
    }
    private const API_URL = 'https://api.bfl.ai/v1/flux-2-pro';

    private const STYLE_BIBLE = <<<STYLE
    VISUAL STYLE BIBLE — STICKMAN CASEBOOK

    GLOBAL VISUAL LANGUAGE

    Create every image as part of the same coherent editorial cartoon universe.

    Style:
    - Editorial spot-illustration style, in the tradition of The New Yorker, The Economist and Bloomberg Businessweek.
    - 2D illustrated artwork for a general, sophisticated readership.
    - Clean, controlled black linework.
    - Simplified geometric forms.
    - Strong readable silhouettes.
    - Moderate visual detail.
    - Sophisticated editorial composition.
    - Restrained, dry visual humor — never cute, playful or whimsical.
    - Muted, restrained editorial color palette. Avoid bright saturated primary colors.
    - Flat illustration with subtle visual depth.
    - Consistent line weight.
    - Clean shapes and controlled proportions.
    - Professional magazine/editorial illustration quality.

    The image must feel intentionally illustrated, not like a collection of isolated cartoon objects.

    BACKGROUND AND ENVIRONMENT

    Environments should contain enough visual information to communicate the situation clearly.

    Use:
    - foreground elements,
    - middle ground,
    - background,
    - overlapping objects,
    - perspective,
    - scale differences,
    - architectural or environmental context,
    - controlled negative space.

    Avoid empty backgrounds unless the scene specifically requires simplicity.

    Avoid excessive detail or visual clutter.

    CHARACTER SYSTEM

    All human figures belong to the same stickman-based visual universe.

    Generic stickmen:
    - completely anonymous figures,
    - simple circular white heads,
    - simple black outlines,
    - simple black stick bodies,
    - minimal facial features,
    - consistent proportions,
    - no clothing,
    - no accessories,
    - no distinctive hairstyle,
    - no distinctive facial identity.

    The Detective Stickman is a separate recurring character with his own reference identity.

    Never merge the Generic Stickman design with the Detective design.

    Never give generic stickmen the Detective's clothing, hat, magnifying glass or other identifying features.

    COMPOSITION

    Prioritize visual storytelling over decoration.

    The image must communicate the scene's central idea immediately.

    Use:
    - clear focal points,
    - asymmetry when useful,
    - diagonals,
    - scale contrast,
    - visual hierarchy,
    - foreground/background separation,
    - strong silhouettes.

    Avoid compositions where the important action is visually lost.

    CHARACTER SCALE

    Characters should serve the composition.

    The Detective normally occupies approximately 15–40% of the image height.

    In wide establishing shots he may be significantly smaller.

    Do not automatically place the Detective in the center.

    Do not make the Detective dominate the frame unless the scene specifically requires it.

    EDITORIAL VISUAL STORYTELLING

    Prefer visual metaphors, exaggeration and symbolic situations when they communicate the narration better than literal representation.

    Financial concepts should be represented visually through:
    - people,
    - environments,
    - objects,
    - exaggerated situations,
    - symbolic structures,
    - scale,
    - movement,
    - contrast.

    Do not automatically create generic financial charts.

    Do not automatically create floating numbers, labels or UI elements.

    MANUAL PRODUCTION ELEMENTS

    Never rely on generated text, labels, percentages, precise figures, tables, charts or numerical information when those elements are specified as manual production elements.

    Those elements will be added later during post-production.

    Do not invent readable text inside the image.

    Do not generate fake statistics or fake labels.

    CAMERA

    Respect the requested shot type.

    Wide:
    - establish environment and relationships.

    Medium:
    - emphasize characters and actions.

    Close-up:
    - emphasize expression, object or important detail.

    Extreme close-up:
    - emphasize a specific visual detail.

    Top-down:
    - use clear overhead composition.

    Low-angle:
    - create visual importance or scale.

    Do not ignore the requested camera distance.

    "STYLE CONSISTENCY"

    Every generated image must look as if it belongs to the same illustration system and the same YouTube channel.

    Maintain consistency in:
    - line quality,
    - character proportions,
    - color treatment,
    - level of detail,
    - geometric simplification,
    - visual depth,
    - editorial tone.

    NEGATIVE STYLE RULES

    Do not use:
    - photorealism,
    - realistic photography,
    - 3D rendering,
    - CGI,
    - anime,
    - manga,
    - Pixar-like rendering,
    - Disney-like rendering,
    - hyper-detailed realism,
    - glossy 3D characters,
    - excessive gradients,
    - excessive visual noise,
    - random decorative elements,
    - inconsistent character designs.
    STYLE;


    public function generateImage(
        Scene $scene,
        array $references = [],
        array $options = []
    ): array {
        $apiKey = config('youtube_studio.flux.api_key');

        $timeout = (int) config(
            'youtube_studio.flux.timeout',
            300
        );

        $pollInterval = (int) config(
            'youtube_studio.flux.poll_interval',
            1
        );

        $maxPollAttempts = (int) config(
            'youtube_studio.flux.max_poll_attempts',
            120
        );

        if (!$apiKey) {
            throw new RuntimeException(
                'BFL_API_KEY no está configurada.'
            );
        }

        $prompt = trim((string) $scene->image_prompt);

        if ($prompt === '') {
            throw new RuntimeException(
                "La escena {$scene->id} no tiene image_prompt."
            );
        }

        $width = (int) ($options['width'] ?? 1536);
        $height = (int) ($options['height'] ?? 864);

        /*
         * ---------------------------------------------------------
         * REFERENCIAS
         * ---------------------------------------------------------
         *
         * Las referencias llegan identificadas por su función:
         *
         * detective_reference_sheet
         * detective_portrait
         * generic_stickman
         *
         * No dependemos del orden del array para saber qué imagen
         * representa cada personaje.
         */

        $referenceImages = $this->prepareReferences($references);

        /*
         * ---------------------------------------------------------
         * PROMPT DE REFERENCIAS
         * ---------------------------------------------------------
         */

        $referenceInstruction = $this->buildReferenceInstruction(
            $references
        );

        /*
         * ---------------------------------------------------------
         * CONTEXTO VISUAL
         * ---------------------------------------------------------
         */

        $visualInstruction = $this->buildVisualInstruction(
            $scene,
            $options
        );

        $rawPrompt = implode("\n\n", array_filter([
            self::STYLE_BIBLE,
            $referenceInstruction,
            $visualInstruction,
        ]));

        $finalPrompt = $this->promptSanitizer->sanitize(
            $rawPrompt
        );

        Log::info('FLUX FINAL PROMPT', [
            'scene_id' => $scene->id,
            'character_role' => $scene->character_role,
            'shot_type' => $scene->shot_type,
            'prompt' => $finalPrompt,
        ]);

        /*
         * ---------------------------------------------------------
         * PAYLOAD
         * ---------------------------------------------------------
         */

        $payload = [
            'prompt' => $finalPrompt,
            'width' => $width,
            'height' => $height,
            'output_format' => 'png',
            'safety_tolerance' => (int) config('youtube_studio.flux.safety_tolerance', 5),
        ];

        /*
         * Añadimos las referencias en el orden en que han sido
         * preparadas.
         */

        foreach ($referenceImages as $index => $base64Image) {
            $parameterName = $index === 0
                ? 'input_image'
                : 'input_image_' . ($index + 1);

            $payload[$parameterName] = $base64Image;
        }
     
        try {
            $response = Http::withHeaders([
                'x-key' => $apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
                ->timeout($timeout)
                ->post(self::API_URL, $payload);
        } catch (ConnectionException $e) {
            throw new RuntimeException(
                'No se pudo conectar con la API de Black Forest Labs: '
                . $e->getMessage(),
                0,
                $e
            );
        }

        if ($response->failed()) {
            throw new RuntimeException(
                'FLUX ha rechazado la petición. HTTP '
                . $response->status()
                . ': '
                . $response->body()
            );
        }

        $data = $response->json();

        if (!is_array($data)) {
            throw new RuntimeException(
                'FLUX ha devuelto una respuesta inválida.'
            );
        }

        $pollingUrl = $data['polling_url'] ?? null;

        if (!$pollingUrl) {
            throw new RuntimeException(
                'FLUX no ha devuelto polling_url. Respuesta: '
                . $response->body()
            );
        }

        $result = $this->pollResult(
            pollingUrl: $pollingUrl,
            apiKey: $apiKey,
            timeout: $timeout,
            pollInterval: $pollInterval,
            maxPollAttempts: $maxPollAttempts,
            sceneId: $scene->id,
            finalPrompt: $finalPrompt,
            references: $references,
        );

        $imageUrl = $result['result']['sample'] ?? null;

        if (!$imageUrl) {
            throw new RuntimeException(
                'FLUX ha terminado pero no ha devuelto '
                . 'la URL de la imagen.'
            );
        }

        return $this->downloadImage(
            imageUrl: $imageUrl,
            timeout: $timeout
        );
    }

    /**
     * Construye las instrucciones específicas de las referencias.
     */
    private function buildReferenceInstruction(
        array $references
    ): string {
        if (empty($references)) {
            return '';
        }

        $instructions = [
            'REFERENCE IMAGES',
            '',
            'Use the provided reference images as authoritative visual references.',
            '',
            'Character identity must be preserved exactly.',
            'Do not redesign, reinterpret or replace the referenced character design.',
            'The references define character identity and anatomy, not the scene composition.',
        ];

        /*
         * ---------------------------------------------------------
         * DETECTIVE
         * ---------------------------------------------------------
         */

        if (
            isset($references['detective_reference_sheet'])
            || isset($references['detective_portrait'])
        ) {
            $instructions[] = '';
            $instructions[] = 'DETECTIVE STICKMAN REFERENCE:';
            $instructions[] =
                'The Detective Stickman reference images define the exact identity of the protagonist.';
            $instructions[] =
                'Preserve the exact head shape, facial features, proportions, clothing, fedora, magnifying glass, line weight and overall character design.';
            $instructions[] =
                'The Detective may change pose, position, scale and expression according to the scene.';
            $instructions[] =
                'Do not redesign the Detective.';
        }

        /*
         * ---------------------------------------------------------
         * GENERIC STICKMAN
         * ---------------------------------------------------------
         */

        if (isset($references['generic_stickman'])) {
            $instructions[] = '';
            $instructions[] = 'GENERIC STICKMAN REFERENCE:';
            $instructions[] =
                'The Generic Stickman reference defines the exact identity and anatomy of all generic stickmen.';
            $instructions[] =
                'All generic stickmen must use the same head shape, body proportions, line weight, eyes, mouth, limbs, hands and feet shown in the reference.';
            $instructions[] =
                'Generic stickmen are anonymous supporting figures and must remain visually interchangeable.';
            $instructions[] =
                'They may change pose, scale, position and expression according to the scene.';
            $instructions[] =
                'Do not give generic stickmen clothing, hats, accessories or distinctive features.';
        }

        /*
         * ---------------------------------------------------------
         * SEPARACIÓN DE PERSONAJES
         * ---------------------------------------------------------
         */

        if (
            isset($references['detective_reference_sheet'])
            || isset($references['detective_portrait'])
        ) {
            if (isset($references['generic_stickman'])) {
                $instructions[] = '';
                $instructions[] = 'CHARACTER SEPARATION:';
                $instructions[] =
                    'The Detective Stickman and Generic Stickmen are two distinct visual identities.';
                $instructions[] =
                    'Never merge their designs.';
                $instructions[] =
                    'Never give generic stickmen the Detective clothing, fedora, magnifying glass or distinctive facial design.';
                $instructions[] =
                    'Never simplify the Detective into a generic stickman.';
            }
        }

        return implode("\n", $instructions);
    }

    /**
     * Añade contexto visual estructurado a partir de la escena.
     *
     * La dirección visual sigue esta jerarquía:
     *
     * visual_concept
     * visual_metaphor
     * visual_description
     * image_prompt
     * character_role
     * shot_type
     */
    private function buildVisualInstruction(
        Scene $scene,
        array $options
    ): string {
        $visualConcept = trim((string) (
            $options['visual_concept']
            ?? $scene->visual_concept
            ?? ''
        ));

        $visualMetaphor = trim((string) (
            $options['visual_metaphor']
            ?? $scene->visual_metaphor
            ?? ''
        ));

        $visualDescription = trim((string) (
            $options['visual_description']
            ?? $scene->visual_description
            ?? ''
        ));

        $imagePrompt = trim((string) (
            $options['image_prompt']
            ?? $scene->image_prompt
            ?? ''
        ));

        $characterRole = trim((string) (
            $options['character_role']
            ?? $scene->character_role
            ?? ''
        ));

        $shotType = trim((string) (
            $options['shot_type']
            ?? $scene->shot_type
            ?? ''
        ));

        $instructions = [
            'VISUAL DIRECTION',
            '',
            'Create the scene as an adult editorial cartoon illustration.',
            'The image must visually support the narration and communicate the main idea of the scene.',
            'Do not create a generic decorative image that is merely related to the topic.',
            'Prioritize visual clarity and narrative relevance.',
        ];

        if ($visualConcept !== '') {
            $instructions[] = '';
            $instructions[] = 'VISUAL CONCEPT:';
            $instructions[] = $visualConcept;
        }

        if ($visualMetaphor !== '') {
            $instructions[] = '';
            $instructions[] = 'VISUAL METAPHOR:';
            $instructions[] = $visualMetaphor;
        }

        if ($visualDescription !== '') {
            $instructions[] = '';
            $instructions[] = 'VISUAL DESCRIPTION:';
            $instructions[] = $visualDescription;
        }

        if ($imagePrompt !== '') {
            $instructions[] = '';
            $instructions[] = 'IMAGE PROMPT:';
            $instructions[] = $imagePrompt;
        }

        if ($characterRole !== '') {
            $instructions[] = '';
            $instructions[] = 'CHARACTER ROLE:';
            $instructions[] = $characterRole;
        }

        if ($shotType !== '') {
            $instructions[] = '';
            $instructions[] = 'SHOT TYPE:';
            $instructions[] = $shotType;
        }

        return implode("\n", $instructions);
    }

    /**
     * Convierte las referencias locales en Base64.
     */
    private function prepareReferences(array $references): array
    {
        $prepared = [];

        $references = array_slice(
            $references,
            0,
            8,
            true
        );

        foreach ($references as $referencePath) {
            if (!$referencePath) {
                continue;
            }

            $contents = $this->readReferenceFile(
                $referencePath
            );

            if ($contents === null) {
                continue;
            }

            $mime = $this->detectMimeType(
                $referencePath,
                $contents
            );

            $prepared[] =
                'data:'
                . $mime
                . ';base64,'
                . base64_encode($contents);
        }

        return $prepared;
    }

    private function readReferenceFile(
        string $referencePath
    ): ?string {
        if (Storage::disk('local')->exists($referencePath)) {
            return Storage::disk('local')->get($referencePath);
        }

        if (Storage::disk('public')->exists($referencePath)) {
            return Storage::disk('public')->get($referencePath);
        }

        if (is_file($referencePath)) {
            $contents = file_get_contents($referencePath);

            if ($contents !== false) {
                return $contents;
            }
        }

        throw new RuntimeException(
            'No se ha encontrado la imagen de referencia: '
            . $referencePath
        );
    }

    private function detectMimeType(
        string $referencePath,
        string $contents
    ): string {
        $extension = strtolower(
            pathinfo($referencePath, PATHINFO_EXTENSION)
        );

        $mimeByExtension = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
        ];

        if (isset($mimeByExtension[$extension])) {
            return $mimeByExtension[$extension];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        $mime = $finfo->buffer($contents);

        if ($mime) {
            return $mime;
        }

        throw new RuntimeException(
            'No se pudo determinar el MIME de la imagen de referencia: '
            . $referencePath
        );
    }

    private function pollResult(
        string $pollingUrl,
        string $apiKey,
        int $timeout,
        int $pollInterval,
        int $maxPollAttempts,
        int|string|null $sceneId = null,
        string $finalPrompt = '',
        array $references = [],
    ): array {
        for ($attempt = 1; $attempt <= $maxPollAttempts; $attempt++) {
            try {
                $response = Http::withHeaders([
                    'x-key' => $apiKey,
                    'Accept' => 'application/json',
                ])
                    ->timeout($timeout)
                    ->get($pollingUrl);
            } catch (ConnectionException $e) {
                if ($attempt >= $maxPollAttempts) {
                    throw new RuntimeException(
                        'Error conectando con el polling de FLUX: ' . $e->getMessage(),
                        0,
                        $e
                    );
                }

                sleep($pollInterval);
                continue;
            }

            if ($response->failed()) {
                throw new RuntimeException(
                    'Error consultando el resultado de FLUX. HTTP '
                    . $response->status() . ': ' . $response->body()
                );
            }

            $data = $response->json();

            if (!is_array($data)) {
                throw new RuntimeException('FLUX ha devuelto un polling inválido.');
            }

            $status = $data['status'] ?? null;

            if ($status === 'Ready') {
                return $data;
            }

            if (
                in_array(
                    $status,
                    ['Error', 'Failed', 'RequestFailed', 'Request Moderated', 'Content Moderated'],
                    true
                )
            ) {
                $reasons = $data['details']['Moderation Reasons'] ?? [];

                Log::error('FLUX CONTENT POLICY', [
                    'scene_id' => $sceneId,
                    'status' => $status,
                    'prompt' => $finalPrompt,
                    'references' => array_keys($references),
                ]);

                Log::warning('FLUX moderó o falló la petición', [
                    'scene_id' => $sceneId,
                    'status' => $status,
                    'moderation_reasons' => $reasons,
                    'raw' => $data,
                ]);

                $message = !empty($reasons)
                    ? implode(', ', $reasons)
                    : ($data['error'] ?? $data['message'] ?? $status ?? 'Error desconocido de FLUX.');

                throw new RuntimeException('FLUX ha fallado (' . $status . '): ' . $message);
            }

            if ($attempt < $maxPollAttempts) {
                sleep($pollInterval);
            }
        }

        throw new RuntimeException(
            'Timeout esperando la generación de FLUX después de '
            . $maxPollAttempts . ' intentos.'
        );
    }

    private function downloadImage(
        string $imageUrl,
        int $timeout
    ): array {
        try {
            $response = Http::timeout($timeout)->get($imageUrl);
        } catch (ConnectionException $e) {
            throw new RuntimeException(
                'No se pudo descargar la imagen generada por FLUX: '
                . $e->getMessage(),
                0,
                $e
            );
        }

        if ($response->failed()) {
            throw new RuntimeException(
                'No se pudo descargar la imagen de FLUX. HTTP '
                . $response->status()
            );
        }

        $contents = $response->body();

        if ($contents === '') {
            throw new RuntimeException(
                'FLUX ha devuelto una imagen vacía.'
            );
        }

        $mime = $response->header('Content-Type')
            ?: 'image/png';

        $extension = match ($mime) {
            'image/jpeg',
            'image/jpg' => 'jpg',

            'image/webp' => 'webp',

            default => 'png',
        };

        return [
            'contents' => $contents,
            'extension' => $extension,
            'mime' => $mime,
        ];
    }
}