<?php

namespace App\Services;

use App\Contracts\Images\ImageProvider;
use App\Models\Project;
use App\Models\Scene;
use Illuminate\Support\Facades\Storage;

class ImageGenerationService
{
    public function __construct(
        private readonly ImageProvider $provider
    ) {
    }

    public function generate(Scene $scene): Scene
    {
        $project = $scene->project()
            ->with('character')
            ->firstOrFail();

        $scene->update([
            'image_status' => 'generating',
        ]);

        try {
            $references = [];

            /*
             * IMPORTANT:
             *
             * Character references are ONLY sent when the scene
             * explicitly contains the Detective.
             *
             * Generic stickmen must NEVER receive the Detective
             * reference images.
             */
            if ($scene->usesDetective()) {
                if ($project->character?->reference_sheet_path) {
                    $references[] = $project
                        ->character
                        ->reference_sheet_path;
                }

                if ($project->character?->portrait_reference_path) {
                    $references[] = $project
                        ->character
                        ->portrait_reference_path;
                }
            }

            $result = $this->provider->generateImage(
                $scene,
                $references,
                [
                    'width' => 1536,
                    'height' => 864,

                    'character_role' => $scene->character_role,

                    'shot_type' => $scene->shot_type,

                    'visual_metaphor' => $scene->visual_metaphor,

                    'uses_character_reference' => $scene->usesDetective(),
                ]
            );

            $extension = $result['extension'] ?? 'png';

            $path = sprintf(
                'projects/%d/images/%03d.%s',
                $project->id,
                $scene->order,
                $extension
            );

            if (
                $scene->image_path
                && $scene->image_path !== $path
                && Storage::disk('local')->exists(
                    $scene->image_path
                )
            ) {
                Storage::disk('local')->delete(
                    $scene->image_path
                );
            }

            Storage::disk('local')->put(
                $path,
                $result['contents']
            );

            $scene->update([
                'image_path' => $path,
                'image_status' => 'generated',
            ]);

            $this->syncProjectStatus(
                $project->refresh()
            );

            return $scene->refresh();
        } catch (\Throwable $e) {
            $scene->update([
                'image_status' => 'error',
            ]);

            $project->update([
                'status' => 'error',
            ]);

            throw $e;
        }
    }

    public function generateAll(Project $project): void
    {
        $project->update([
            'status' => 'images_generating',
        ]);

        $scenes = $project->scenes()
            ->orderBy('order')
            ->get();

        foreach ($scenes as $scene) {
            $this->generate($scene);
        }

        $project->refresh();

        $total = $project->scenes()->count();

        $generated = $project->scenes()
            ->where('image_status', 'generated')
            ->count();

        if ($total > 0 && $generated === $total) {
            $project->update([
                'status' => 'images_ready',
            ]);
        }
    }

    private function syncProjectStatus(
        Project $project
    ): void {
        $total = $project->scenes()->count();

        $generated = $project->scenes()
            ->where('image_status', 'generated')
            ->count();

        if ($total > 0 && $generated === $total) {
            $project->update([
                'status' => 'images_ready',
            ]);

            return;
        }

        $project->update([
            'status' => 'images_generating',
        ]);
    }
}