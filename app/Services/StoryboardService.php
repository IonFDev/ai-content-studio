<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Scene;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class StoryboardService
{
    public function generate(Project $project): Project
    {
        $path = "projects/{$project->id}/storyboard/content.json";

        if (!Storage::disk('local')->exists($path)) {
            throw new RuntimeException(
                'Generate content before generating the storyboard.'
            );
        }

        $json = Storage::disk('local')->get($path);

        $data = json_decode($json, true);

        if (!is_array($data)) {
            throw new RuntimeException(
                'El contenido del storyboard no contiene un JSON válido.'
            );
        }

        if (
            !isset($data['scenes'])
            || !is_array($data['scenes'])
            || count($data['scenes']) === 0
        ) {
            throw new RuntimeException(
                'El contenido generado no contiene escenas.'
            );
        }

        DB::transaction(function () use ($project, $data) {
            $project->scenes()->delete();

            foreach ($data['scenes'] as $index => $scene) {
                $visualDescription = trim(
                    (string) ($scene['visual_description'] ?? '')
                );

                $characterRole = $this->normalizeCharacterRole(
                    $scene['character_role'] ?? 'none'
                );

                $shotType = $this->normalizeShotType(
                    $scene['camera'] ?? null
                );

                $manualElements = $scene['manual_elements'] ?? [];

                if (!is_array($manualElements)) {
                    $manualElements = [];
                }

                $project->scenes()->create([
                    'order' => (int) ($scene['order'] ?? ($index + 1)),

                    'narration' => trim(
                        (string) ($scene['narration'] ?? '')
                    ),

                    'visual_description' => $visualDescription !== ''
                        ? $visualDescription
                        : null,

                    'character_role' => $characterRole,

                    'visual_metaphor' => $this->nullableString(
                        $scene['visual_metaphor'] ?? null
                    ),

                    'shot_type' => $shotType,

                    'image_prompt' => $this->nullableString(
                        $scene['image_prompt'] ?? null
                    ),

                    'image_status' => 'pending',

                    'manual_elements' => $manualElements,

                    'animation_notes' => $this->nullableString(
                        $scene['animation_notes'] ?? null
                    ),

                    'production_notes' => $this->nullableString(
                        $scene['production_notes'] ?? null
                    ),
                ]);
            }

            $project->update([
                'status' => 'storyboard_ready',
            ]);
        });

        return $project->refresh();
    }

    private function normalizeCharacterRole(mixed $value): string
    {
        $value = strtolower(trim((string) $value));

        if (!in_array($value, Scene::CHARACTER_ROLES, true)) {
            return 'none';
        }

        return $value;
    }

    private function normalizeShotType(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = strtolower(trim((string) $value));

        if ($value === '') {
            return null;
        }

        if (in_array($value, Scene::SHOT_TYPES, true)) {
            return $value;
        }

        return 'medium_wide';
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    public function reorder(
        Project $project,
        array $orders
    ): void {
        DB::transaction(function () use ($project, $orders) {
            $scenes = $project->scenes()
                ->whereIn('id', $orders)
                ->get()
                ->keyBy('id');

            if ($scenes->count() !== count($orders)) {
                throw new RuntimeException(
                    'Una o más escenas no pertenecen a este proyecto.'
                );
            }

            foreach ($scenes as $scene) {
                $scene->update([
                    'order' => $scene->order + 1000000,
                ]);
            }

            foreach ($orders as $index => $sceneId) {
                $scenes[$sceneId]->update([
                    'order' => $index + 1,
                ]);
            }
        });
    }
}