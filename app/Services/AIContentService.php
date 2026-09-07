<?php

namespace App\Services;

use App\Contracts\AI\AIProvider;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class AIContentService
{
    public function __construct(
        private readonly AIProvider $provider
    ) {
    }

    public function generate(Project $project): Project
    {
        if (blank($project->transcript)) {
            throw new RuntimeException(
                'A transcript is required before generating content.'
            );
        }

        $data = $this->provider->generateContent(
            $project,
            $project->transcript
        );

        $this->validateClaudeData($data);

        DB::transaction(function () use ($project, $data) {
            $video = $data['video'];
            $content = $data['content'];
            $thumbnail = $video['thumbnail'] ?? [];

            $project->update([
                'youtube_title' => $video['title'] ?? null,

                'youtube_description' => $video['description'] ?? null,

                'youtube_keywords' => $video['keywords'] ?? [],

                'youtube_hashtags' => $video['hashtags'] ?? [],

                'thumbnail_idea' => $thumbnail['concept'] ?? null,

                'thumbnail_text' => $thumbnail['text'] ?? null,

                'script' => $content['script'] ?? null,

                'status' => 'script_ready',
            ]);

            Storage::disk('local')->put(
                "projects/{$project->id}/storyboard/content.json",
                json_encode(
                    $data,
                    JSON_PRETTY_PRINT
                    | JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                )
            );
        });

        return $project->refresh();
    }

    private function validateClaudeData(array $data): void
    {
        if (!isset($data['video']) || !is_array($data['video'])) {
            throw new RuntimeException(
                'La respuesta de Claude no contiene una sección video válida.'
            );
        }

        if (!isset($data['content']) || !is_array($data['content'])) {
            throw new RuntimeException(
                'La respuesta de Claude no contiene una sección content válida.'
            );
        }

        if (
            !isset($data['scenes'])
            || !is_array($data['scenes'])
            || count($data['scenes']) === 0
        ) {
            throw new RuntimeException(
                'La respuesta de Claude no contiene escenas.'
            );
        }

        if (
            !isset($data['content']['script'])
            || !is_string($data['content']['script'])
            || trim($data['content']['script']) === ''
        ) {
            throw new RuntimeException(
                'La respuesta de Claude no contiene un guion válido.'
            );
        }

        foreach ($data['scenes'] as $index => $scene) {
            if (!is_array($scene)) {
                throw new RuntimeException(
                    "La escena {$index} no tiene un formato válido."
                );
            }

            foreach ([
                'id',
                'order',
                'narration',
                'visual',
                'image_prompt',
            ] as $field) {
                if (!array_key_exists($field, $scene)) {
                    throw new RuntimeException(
                        "La escena {$index} no contiene el campo '{$field}'."
                    );
                }
            }

            if (!is_array($scene['visual'])) {
                throw new RuntimeException(
                    "La escena {$index} contiene un visual inválido."
                );
            }
        }
    }
}