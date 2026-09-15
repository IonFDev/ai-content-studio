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

                /*
                 * Nuevo formato:
                 *
                 * {
                 *   "order": 1,
                 *   "narration": "...",
                 *   "visual_description": "...",
                 *   "character_role": "detective",
                 *   "shot_type": "medium_wide",
                 *   "visual_metaphor": "...",
                 *   "image_prompt": "...",
                 *   "manual_elements": [],
                 *   "animation_notes": "..."
                 * }
                 *
                 * También soportamos temporalmente el formato antiguo
                 * con "visual" y "production".
                 */

                $visual = is_array($scene['visual'] ?? null)
                    ? $scene['visual']
                    : [];

                $production = is_array($scene['production'] ?? null)
                    ? $scene['production']
                    : [];

                /*
                 * Dirección visual
                 */

                $visualDescription = $scene['visual_description']
                    ?? $visual['description']
                    ?? null;

                $characterRole = $scene['character_role']
                    ?? $visual['character_role']
                    ?? 'none';

                $shotType = $scene['shot_type']
                    ?? $visual['shot_type']
                    ?? $visual['camera']
                    ?? 'medium';

                $visualMetaphor = $scene['visual_metaphor']
                    ?? $visual['visual_metaphor']
                    ?? null;

                /*
                 * Producción
                 */

                $manualElements = $scene['manual_elements']
                    ?? $production['manual_elements']
                    ?? [];

                if (!is_array($manualElements)) {
                    $manualElements = [];
                }

                $animationNotes = $scene['animation_notes']
                    ?? null;

                /*
                 * Compatibilidad con el formato antiguo.
                 */

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
                 * Validamos los valores controlados.
                 *
                 * Si Claude devuelve algo inesperado, no dejamos que
                 * contamine la base de datos.
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
                 * Normalización.
                 */

                $manualElements = array_values(
                    array_filter(
                        array_map(
                            fn ($item) => trim((string) $item),
                            $manualElements
                        )
                    )
                );

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

                    'visual_description' => $visualDescription !== null
                        ? trim((string) $visualDescription)
                        : null,

                    'character_role' => $characterRole,

                    'shot_type' => $shotType,

                    'visual_metaphor' => $visualMetaphor !== null
                        ? trim((string) $visualMetaphor)
                        : null,

                    /*
                     * visual_priority queda preparado en DB,
                     * pero todavía no obligamos a Claude a generarlo.
                     */
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

    private function formatAnimationNotes(array $animation): ?string
    {
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