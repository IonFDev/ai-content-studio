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

            $project->update([
                'youtube_title' => $video['title'] ?? null,

                'youtube_description' => $video['description'] ?? null,

                'youtube_keywords' => $video['keywords'] ?? [],

                'youtube_hashtags' => $video['hashtags'] ?? [],

                'thumbnail_idea' => $video['thumbnail_concept'] ?? null,

                'thumbnail_text' => $video['thumbnail_text'] ?? null,

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
        /*
        |--------------------------------------------------------------------------
        | Estructura principal
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Video
        |--------------------------------------------------------------------------
        */

        foreach ([
            'title',
            'alternative_titles',
            'description',
            'keywords',
            'hashtags',
            'thumbnail_concept',
            'thumbnail_text',
        ] as $field) {
            if (!array_key_exists($field, $data['video'])) {
                throw new RuntimeException(
                    "La sección video no contiene el campo '{$field}'."
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Content
        |--------------------------------------------------------------------------
        */

        foreach ([
            'hook',
            'script',
            'structure',
        ] as $field) {
            if (!array_key_exists($field, $data['content'])) {
                throw new RuntimeException(
                    "La sección content no contiene el campo '{$field}'."
                );
            }
        }

        if (
            !is_string($data['content']['script'])
            || trim($data['content']['script']) === ''
        ) {
            throw new RuntimeException(
                'La respuesta de Claude no contiene un guion válido.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Scenes
        |--------------------------------------------------------------------------
        */

        $sceneCount = count($data['scenes']);

        if ($sceneCount < 25) {
            throw new RuntimeException(
                "Claude solo ha generado {$sceneCount} escenas. "
                . 'Se esperaban al menos 25 escenas para este tipo de vídeo.'
            );
        }

        if ($sceneCount > 40) {
            throw new RuntimeException(
                "Claude ha generado {$sceneCount} escenas. "
                . 'El máximo permitido es 40 para mantener el storyboard eficiente.'
            );
        }

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

        foreach ($data['scenes'] as $index => $scene) {
            $sceneNumber = $index + 1;

            if (!is_array($scene)) {
                throw new RuntimeException(
                    "La escena {$sceneNumber} no tiene un formato válido."
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Campos obligatorios de la escena
            |--------------------------------------------------------------------------
            */

            foreach ([
                'order',
                'narration',
                'visual_description',
                'character_role',
                'shot_type',
                'visual_metaphor',
                'image_prompt',
                'manual_elements',
                'animation_notes',
            ] as $field) {
                if (!array_key_exists($field, $scene)) {
                    throw new RuntimeException(
                        "La escena {$sceneNumber} no contiene el campo requerido: {$field}."
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Order
            |--------------------------------------------------------------------------
            */

            if (!is_int($scene['order'])) {
                throw new RuntimeException(
                    "La escena {$sceneNumber} tiene un order inválido."
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Narration
            |--------------------------------------------------------------------------
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
            |--------------------------------------------------------------------------
            | Visual description
            |--------------------------------------------------------------------------
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
            |--------------------------------------------------------------------------
            | Character role
            |--------------------------------------------------------------------------
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
            |--------------------------------------------------------------------------
            | Shot type
            |--------------------------------------------------------------------------
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
            |--------------------------------------------------------------------------
            | Visual metaphor
            |--------------------------------------------------------------------------
            */

            if (
                !is_string($scene['visual_metaphor'])
                || trim($scene['visual_metaphor']) === ''
            ) {
                throw new RuntimeException(
                    "La escena {$sceneNumber} no contiene una visual_metaphor válida."
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Image prompt
            |--------------------------------------------------------------------------
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
            |--------------------------------------------------------------------------
            | Manual elements
            |--------------------------------------------------------------------------
            */

            if (!is_array($scene['manual_elements'])) {
                throw new RuntimeException(
                    "La escena {$sceneNumber} tiene manual_elements inválido."
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Animation notes
            |--------------------------------------------------------------------------
            */

            if (!is_string($scene['animation_notes'])) {
                throw new RuntimeException(
                    "La escena {$sceneNumber} tiene animation_notes inválido."
                );
            }
        }
    }
}