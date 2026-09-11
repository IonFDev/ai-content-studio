<?php

namespace App\Services;

use App\Models\Project;
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
                $visual = $scene['visual'] ?? [];
                $production = $scene['production'] ?? [];

                $project->scenes()->create([
                    'order' => (int) ($scene['order'] ?? ($index + 1)),
                    'narration' => $scene['narration'] ?? '',
                    'visual_description' => $visual['description'] ?? null,
                    'image_prompt' => $scene['image_prompt'] ?? null,
                    'image_status' => 'pending',

                    'manual_elements' => $production['manual_elements'] ?? [],
                    'animation_notes' => $this->formatAnimationNotes(
                        $production['animation'] ?? []
                    ),
                    'production_notes' => $production['notes'] ?? null,
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

        return empty($animation)
            ? null
            : implode("\n", array_map(
                fn ($item, $index) => ($index + 1) . '. ' . $item,
                $animation,
                array_keys($animation)
            ));
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