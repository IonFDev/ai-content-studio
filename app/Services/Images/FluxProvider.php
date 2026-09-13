<?php

namespace App\Services\Images;

use App\Contracts\Images\ImageProvider;
use App\Models\Scene;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class FluxProvider implements ImageProvider
{
    private const API_URL = 'https://api.bfl.ai/v1/flux-2-pro';

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
         * FLUX.2 utiliza:
         *
         * input_image
         * input_image_2
         * input_image_3
         * ...
         *
         * Las referencias que tenemos en Laravel son rutas locales,
         * así que debemos convertirlas a Base64.
         */

        $referenceImages = $this->prepareReferences($references);

        /*
         * ---------------------------------------------------------
         * PROMPT
         * ---------------------------------------------------------
         *
         * Dejamos explícito qué representa cada referencia para que
         * FLUX sepa que debe mantener la identidad visual del personaje.
         */

        $referenceInstruction = '';

        if (count($referenceImages) > 0) {
            $referenceInstruction = <<<PROMPT

Use the provided reference images as authoritative visual references.

Image 1 is the definitive character reference.
Image 2 is an additional character/style reference.

Preserve the exact visual identity, proportions, head shape,
facial features, eyes, mouth, clothing design, hat design,
magnifying glass design, line weight and minimalist stickman
aesthetic from the reference images.

Do not redesign the character.
Do not turn the character into a realistic human.
Do not change the character into another cartoon style.
The generated character must clearly be the same Detective Stickman
shown in the reference images.

PROMPT;
        }

        $finalPrompt = trim(
            $referenceInstruction
            . "\n\n"
            . $prompt
        );

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
        ];

        /*
         * Añadimos las referencias dinámicamente:
         *
         * input_image
         * input_image_2
         * ...
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
            maxPollAttempts: $maxPollAttempts
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
     * Convierte las rutas locales de las referencias en imágenes
     * Base64 que BFL pueda recibir mediante input_image_N.
     */
    private function prepareReferences(array $references): array
    {
        $prepared = [];

        /*
         * FLUX.2 Pro admite hasta 8 imágenes de referencia
         * mediante la API.
         */
        $references = array_slice($references, 0, 8);

        foreach ($references as $referencePath) {
            if (!$referencePath) {
                continue;
            }

            $contents = $this->readReferenceFile($referencePath);

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

    /**
     * Intenta localizar una referencia tanto en storage/app
     * como en storage/app/public.
     */
    private function readReferenceFile(
        string $referencePath
    ): ?string {
        /*
         * 1. Intentar disco local de Laravel.
         */
        if (Storage::disk('local')->exists($referencePath)) {
            return Storage::disk('local')->get($referencePath);
        }

        /*
         * 2. Intentar disco public.
         */
        if (Storage::disk('public')->exists($referencePath)) {
            return Storage::disk('public')->get($referencePath);
        }

        /*
         * 3. Intentar ruta absoluta.
         */
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

    /**
     * Detecta el MIME de la imagen.
     */
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
        int $maxPollAttempts
    ): array {
        for (
            $attempt = 1;
            $attempt <= $maxPollAttempts;
            $attempt++
        ) {
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
                        'Error conectando con el polling de FLUX: '
                        . $e->getMessage(),
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
                    . $response->status()
                    . ': '
                    . $response->body()
                );
            }

            $data = $response->json();

            if (!is_array($data)) {
                throw new RuntimeException(
                    'FLUX ha devuelto un polling inválido.'
                );
            }

            $status = $data['status'] ?? null;

            if ($status === 'Ready') {
                return $data;
            }

            if (
                in_array(
                    $status,
                    [
                        'Error',
                        'Failed',
                        'RequestFailed',
                        'Request Moderated',
                        'Content Moderated',
                    ],
                    true
                )
            ) {
                $message =
                    $data['error']
                    ?? $data['message']
                    ?? $status
                    ?? 'Error desconocido de FLUX.';

                throw new RuntimeException(
                    'FLUX ha fallado: ' . $message
                );
            }

            if ($attempt < $maxPollAttempts) {
                sleep($pollInterval);
            }
        }

        throw new RuntimeException(
            'Timeout esperando la generación de FLUX después de '
            . $maxPollAttempts
            . ' intentos.'
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
