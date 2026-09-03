<?php

namespace App\Services\Transcription;

use App\Contracts\Transcription\TranscriptionProvider;
use App\Models\Project;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WhisperProvider implements TranscriptionProvider
{
    public function transcribe(Project $project): string
    {
        if (!$project->source_url) {
            throw new RuntimeException('El proyecto no tiene una URL de YouTube.');
        }

        $response = Http::timeout((int) config('youtube_studio.whisper.timeout', 3600))
            ->acceptJson()
            ->post(config('youtube_studio.whisper.url') . '/transcribe', [
                'project_id' => $project->id,
                'source_url' => $project->source_url,
                'language' => config('youtube_studio.whisper.language'),
            ]);

        if ($response->failed()) {
            $detail = $response->json('detail') ?? $response->body();
            throw new RuntimeException('Whisper: ' . $detail);
        }

        $text = trim((string) $response->json('text'));
        if ($text === '') {
            throw new RuntimeException('Whisper no ha devuelto una transcripción.');
        }

        return $text;
    }
}
