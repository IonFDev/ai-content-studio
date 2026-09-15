<?php

namespace App\Services\Voice;

use App\Contracts\Voice\VoiceProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ElevenLabsProvider implements VoiceProvider
{
    private string $apiKey;
    private string $voiceId;
    private string $model;
    private string $outputFormat;
    private int $timeout;

    public function __construct()
    {
        $this->apiKey = (string) config('youtube_studio.elevenlabs.api_key');
        $this->voiceId = (string) config('youtube_studio.elevenlabs.voice_id');

        $this->model = (string) config(
            'youtube_studio.elevenlabs.model',
            'eleven_multilingual_v2'
        );

        $this->outputFormat = (string) config(
            'youtube_studio.elevenlabs.output_format',
            'mp3_44100_128'
        );

        $this->timeout = (int) config(
            'youtube_studio.elevenlabs.timeout',
            300
        );

        if ($this->apiKey === '') {
            throw new RuntimeException(
                'ELEVENLABS_API_KEY no está configurada.'
            );
        }

        if ($this->voiceId === '') {
            throw new RuntimeException(
                'ELEVENLABS_VOICE_ID no está configurada.'
            );
        }
    }

    public function generate(string $text): array
    {
        $text = trim($text);

        if ($text === '') {
            throw new RuntimeException(
                'No se puede generar voz con un texto vacío.'
            );
        }

        $url = sprintf(
            'https://api.elevenlabs.io/v1/text-to-speech/%s/with-timestamps',
            $this->voiceId
        );

        $response = Http::timeout($this->timeout)
            ->acceptJson()
            ->withHeaders([
                'xi-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ])
            ->withQueryParameters([
                'output_format' => $this->outputFormat,
            ])
            ->post($url, [
                'text' => $text,
                'model_id' => $this->model,
            ]);

        if ($response->failed()) {
            $detail = $response->json('detail');

            if (is_array($detail)) {
                $detail = json_encode(
                    $detail,
                    JSON_UNESCAPED_UNICODE
                );
            }

            throw new RuntimeException(
                'ElevenLabs devolvió HTTP '
                . $response->status()
                . ': '
                . ($detail ?: $response->body())
            );
        }

        $data = $response->json();

        if (!isset($data['audio_base64'])) {
            throw new RuntimeException(
                'ElevenLabs no devolvió audio_base64.'
            );
        }

        if (!isset($data['alignment'])) {
            throw new RuntimeException(
                'ElevenLabs no devolvió alignment.'
            );
        }

        $alignment = $data['alignment'];

        $required = [
            'characters',
            'character_start_times_seconds',
            'character_end_times_seconds',
        ];

        foreach ($required as $key) {
            if (!isset($alignment[$key])) {
                throw new RuntimeException(
                    "El alignment de ElevenLabs no contiene '{$key}'."
                );
            }
        }

        $audio = base64_decode(
            $data['audio_base64'],
            true
        );

        if ($audio === false) {
            throw new RuntimeException(
                'No se pudo decodificar el audio de ElevenLabs.'
            );
        }

        return [
            'audio' => $audio,

            'alignment' => [
                'characters' => array_values(
                    $alignment['characters']
                ),

                'character_start_times_seconds' => array_map(
                    'floatval',
                    $alignment['character_start_times_seconds']
                ),

                'character_end_times_seconds' => array_map(
                    'floatval',
                    $alignment['character_end_times_seconds']
                ),
            ],
        ];
    }
}