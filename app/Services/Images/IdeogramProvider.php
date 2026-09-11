<?php

namespace App\Services\Images;

use App\Contracts\Images\ImageProvider;
use App\Models\Project;
use App\Models\Scene;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class IdeogramProvider implements ImageProvider
{
    private const API_URL = 'https://api.ideogram.ai/v1/ideogram-v3/generate';

    public function generate(Scene $scene, Project $project): string
    {
        $apiKey = config('youtube_studio.ideogram.api_key');

        if (!$apiKey) {
            throw new RuntimeException(
                'IDEOGRAM_API_KEY no está configurada.'
            );
        }

        $imagePrompt = trim((string) $scene->image_prompt);

        if ($imagePrompt === '') {
            throw new RuntimeException(
                "La escena {$scene->id} no tiene image_prompt."
            );
        }

        $prompt = $this->buildPrompt($scene);

        $multipart = [
            [
                'name' => 'prompt',
                'contents' => $prompt,
            ],
            [
                'name' => 'aspect_ratio',
                'contents' => config(
                    'youtube_studio.ideogram.aspect_ratio',
                    '16x9'
                ),
            ],
            [
                'name' => 'rendering_speed',
                'contents' => config(
                    'youtube_studio.ideogram.rendering_speed',
                    'TURBO'
                ),
            ],
            [
                'name' => 'magic_prompt',
                'contents' => config(
                    'youtube_studio.ideogram.magic_prompt',
                    'OFF'
                ),
            ],
            [
                'name' => 'style_type',
                'contents' => 'AUTO',
            ],
            [
                'name' => 'num_images',
                'contents' => '1',
            ],
        ];

        if ($this->sceneNeedsCharacterReference($scene)) {
            $characterReference = $this->getCharacterReference();

            $multipart[] = [
                'name' => 'character_reference_images',
                'contents' => fopen($characterReference['path'], 'rb'),
                'filename' => $characterReference['filename'],
                'headers' => [
                    'Content-Type' => $characterReference['mime'],
                ],
            ];

            $styleReference = $this->getStyleReference();

            $multipart[] = [
                'name' => 'style_reference_images',
                'contents' => fopen($styleReference['path'], 'rb'),
                'filename' => $styleReference['filename'],
                'headers' => [
                    'Content-Type' => $styleReference['mime'],
                ],
            ];
        }

        try {
            $response = Http::timeout(
                config('youtube_studio.ideogram.timeout', 300)
            )
                ->withHeaders([
                    'Api-Key' => $apiKey,
                    'Accept' => 'application/json',
                ])
                ->asMultipart()
                ->post(self::API_URL, $multipart);
        } catch (ConnectionException $e) {
            throw new RuntimeException(
                'No se pudo conectar con Ideogram: ' . $e->getMessage(),
                0,
                $e
            );
        }

        if ($response->failed()) {
            throw $this->apiException($response);
        }

        $data = $response->json();

        $imageUrl = data_get($data, 'data.0.url');

        if (!$imageUrl) {
            throw new RuntimeException(
                'Ideogram respondió correctamente, pero no devolvió ninguna imagen.'
            );
        }

        return $this->downloadImage(
            $imageUrl,
            $project,
            $scene
        );
    }

    private function buildPrompt(Scene $scene): string
    {
        $baseStyle = implode(' ', [
            'Minimalist black stickman line art.',
            'Clean white background.',
            'Flat 2D illustration.',
            'Simple shapes.',
            'High contrast.',
            'Black dominant palette with white and grayscale only.',
            'Generous white space.',
            'Thin clean black lines.',
            'Consistent visual language.',
            'No photorealism.',
            'No 3D.',
            'No anime.',
            'No painterly style.',
            'No cinematic realism.',
            'No gradients.',
            'No decorative colors.',
            'No complex background.',
        ]);

        return trim(
            $scene->image_prompt . ' ' . $baseStyle
        );
    }

    private function sceneNeedsCharacterReference(Scene $scene): bool
    {
        /*
         * De momento usamos una comprobación sencilla basada en el
         * image_prompt generado por Claude.
         *
         * Más adelante lo podremos hacer de forma estructurada
         * añadiendo character_reference al JSON de cada escena.
         */

        $prompt = mb_strtolower(
            (string) $scene->image_prompt
        );

        return str_contains($prompt, 'stickman')
            || str_contains($prompt, 'detective');
    }

    private function getCharacterReference(): array
    {
        $path = config(
            'youtube_studio.ideogram.character_reference_path'
        );

        if (!$path) {
            throw new RuntimeException(
                'No se ha configurado la referencia del personaje.'
            );
        }

        if (!Storage::disk('local')->exists($path)) {
            throw new RuntimeException(
                "No existe la referencia del personaje: {$path}"
            );
        }

        $absolutePath = Storage::disk('local')->path($path);

        $mime = mime_content_type($absolutePath);

        if (!$mime || !in_array($mime, [
            'image/png',
            'image/jpeg',
            'image/webp',
        ], true)) {
            throw new RuntimeException(
                'La referencia del personaje debe ser PNG, JPEG o WebP.'
            );
        }

        return [
            'path' => $absolutePath,
            'filename' => basename($absolutePath),
            'mime' => $mime,
        ];
    }

    private function getStyleReference(): array
    {
        $path = config(
            'youtube_studio.ideogram.style_reference_path'
        );

        if (!$path) {
            throw new RuntimeException(
                'No se ha configurado la referencia de estilo del personaje.'
            );
        }

        if (!Storage::disk('local')->exists($path)) {
            throw new RuntimeException(
                "No existe la referencia de estilo: {$path}"
            );
        }

        $absolutePath = Storage::disk('local')->path($path);

        $mime = mime_content_type($absolutePath);

        if (!$mime || !in_array($mime, [
            'image/png',
            'image/jpeg',
            'image/webp',
        ], true)) {
            throw new RuntimeException(
                'La referencia de estilo debe ser PNG, JPEG o WebP.'
            );
        }

        return [
            'path' => $absolutePath,
            'filename' => basename($absolutePath),
            'mime' => $mime,
        ];
    }

    private function downloadImage(
        string $url,
        Project $project,
        Scene $scene
    ): string {
        try {
            $response = Http::timeout(180)->get($url);
        } catch (ConnectionException $e) {
            throw new RuntimeException(
                'No se pudo descargar la imagen generada por Ideogram.',
                0,
                $e
            );
        }

        if ($response->failed()) {
            throw new RuntimeException(
                'Ideogram generó la imagen, pero Laravel no pudo descargarla. '
                . 'HTTP ' . $response->status()
            );
        }

        $directory = "projects/{$project->id}/images";

        Storage::disk('local')->makeDirectory($directory);

        $filename = sprintf(
            'scene-%03d.png',
            $scene->order
        );

        $path = "{$directory}/{$filename}";

        Storage::disk('local')->put(
            $path,
            $response->body()
        );

        return $path;
    }

    private function apiException($response): RuntimeException
    {
        $body = $response->json();

        $message =
            data_get($body, 'error.message')
            ?? data_get($body, 'message')
            ?? $response->body();

        return new RuntimeException(
            sprintf(
                'Ideogram API error (%s): %s',
                $response->status(),
                $message
            )
        );
    }
}