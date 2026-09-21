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
                if (!is_array($scene)) {
                    continue;
                }

                $visual = is_array($scene['visual'] ?? null)
                    ? $scene['visual']
                    : [];

                $production = is_array($scene['production'] ?? null)
                    ? $scene['production']
                    : [];

                /*
                |--------------------------------------------------------------------------
                | Visual concept
                |--------------------------------------------------------------------------
                */

                $visualConcept = $scene['visual_concept']
                    ?? $visual['visual_concept']
                    ?? null;

                /*
                |--------------------------------------------------------------------------
                | Visual description
                |--------------------------------------------------------------------------
                */

                $visualDescription = $scene['visual_description']
                    ?? $visual['description']
                    ?? null;

                /*
                |--------------------------------------------------------------------------
                | Character role
                |--------------------------------------------------------------------------
                */

                $characterRole = $scene['character_role']
                    ?? $visual['character_role']
                    ?? 'none';

                /*
                |--------------------------------------------------------------------------
                | Shot type
                |--------------------------------------------------------------------------
                */

                $shotType = $scene['shot_type']
                    ?? $visual['shot_type']
                    ?? $visual['camera']
                    ?? 'medium';

                /*
                |--------------------------------------------------------------------------
                | Visual metaphor
                |--------------------------------------------------------------------------
                */

                $visualMetaphor = $scene['visual_metaphor']
                    ?? $visual['visual_metaphor']
                    ?? null;

                /*
                |--------------------------------------------------------------------------
                | Manual elements
                |--------------------------------------------------------------------------
                */

                $manualElements = $scene['manual_elements']
                    ?? $production['manual_elements']
                    ?? [];

                $manualElements = $this->normalizeManualElements(
                    $manualElements
                );

                /*
                |--------------------------------------------------------------------------
                | Animation notes
                |--------------------------------------------------------------------------
                */

                $animationNotes = $scene['animation_notes']
                    ?? null;

                if (
                    $animationNotes === null
                    && isset($production['animation'])
                    && is_array($production['animation'])
                ) {
                    $animationNotes = $this->formatAnimationNotes(
                        $production['animation']
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Validate character role
                |--------------------------------------------------------------------------
                */

                if (
                    !in_array(
                        $characterRole,
                        Scene::CHARACTER_ROLES,
                        true
                    )
                ) {
                    $characterRole = 'none';
                }

                /*
                |--------------------------------------------------------------------------
                | Validate shot type
                |--------------------------------------------------------------------------
                */

                if (
                    !in_array(
                        $shotType,
                        Scene::SHOT_TYPES,
                        true
                    )
                ) {
                    $shotType = 'medium';
                }

                /*
                |--------------------------------------------------------------------------
                | Create scene
                |--------------------------------------------------------------------------
                */

                $project->scenes()->create([
                    'order' => (int) (
                        $scene['order']
                        ?? ($index + 1)
                    ),

                    'narration' => trim(
                        (string) (
                            $scene['narration']
                            ?? ''
                        )
                    ),

                    'visual_concept' => $visualConcept !== null
                        ? trim((string) $visualConcept)
                        : null,

                    'visual_description' => $visualDescription !== null
                        ? trim((string) $visualDescription)
                        : null,

                    'character_role' => $characterRole,

                    'shot_type' => $shotType,

                    'visual_metaphor' => $visualMetaphor !== null
                        ? trim((string) $visualMetaphor)
                        : null,

                    'visual_priority' => null,

                    'image_prompt' => isset($scene['image_prompt'])
                        ? trim((string) $scene['image_prompt'])
                        : null,

                    'image_status' => 'pending',

                    'manual_elements' => $manualElements,

                    'animation_notes' => $animationNotes !== null
                        ? trim((string) $animationNotes)
                        : null,

                    'production_notes' => isset(
                        $production['notes']
                    )
                        ? trim((string) $production['notes'])
                        : null,
                ]);
            }

            $project->update([
                'status' => 'storyboard_ready',
            ]);
        });

        return $project->refresh();
    }

    /**
     * Normaliza los elementos visuales que deben añadirse
     * manualmente durante la edición.
     *
     * Soporta tanto el formato nuevo estructurado como
     * strings procedentes de proyectos antiguos.
     */
    private function normalizeManualElements(
        mixed $manualElements
    ): array {
        if (!is_array($manualElements)) {
            return [];
        }

        $normalized = [];

        foreach ($manualElements as $item) {
            // Compatibilidad con el formato antiguo:
            // ["Añadir un porcentaje", "Añadir una flecha"]
            if (is_string($item)) {
                $description = trim($item);

                if ($description === '') {
                    continue;
                }

                $normalized[] = [
                    'type' => 'elemento',
                    'description' => $description,
                    'details' => '',
                    'position' => '',
                ];

                continue;
            }

            if (!is_array($item)) {
                continue;
            }

            $type = trim(
                (string) (
                    $item['type']
                    ?? 'elemento'
                )
            );

            $description = trim(
                (string) (
                    $item['description']
                    ?? ''
                )
            );

            $details = trim(
                (string) (
                    $item['details']
                    ?? ''
                )
            );

            $position = trim(
                (string) (
                    $item['position']
                    ?? ''
                )
            );

            if ($description === '') {
                continue;
            }

            $normalized[] = [
                'type' => $type !== ''
                    ? $type
                    : 'elemento',
                'description' => $description,
                'details' => $details,
                'position' => $position,
            ];
        }

        return array_values($normalized);
    }

    private function formatAnimationNotes(
        array $animation
    ): ?string {
        $animation = array_values(
            array_filter(
                array_map(
                    fn ($item) => trim((string) $item),
                    $animation
                )
            )
        );

        if (empty($animation)) {
            return null;
        }

        return implode(
            "\n",
            array_map(
                fn ($item, $index) => ($index + 1) . '. ' . $item,
                $animation,
                array_keys($animation)
            )
        );
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
