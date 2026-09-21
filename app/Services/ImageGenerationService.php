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
            $references = $this->buildReferences(
                $scene,
                $project
            );

            $result = $this->provider->generateImage(
                $scene,
                $references,
                [
                    /*
                    |--------------------------------------------------------------------------
                    | Output
                    |--------------------------------------------------------------------------
                    */

                    'width' => 1536,
                    'height' => 864,

                    /*
                    |--------------------------------------------------------------------------
                    | Scene direction
                    |--------------------------------------------------------------------------
                    */

                    'visual_concept' => $scene->visual_concept,

                    'visual_description' => $scene->visual_description,

                    'visual_metaphor' => $scene->visual_metaphor,

                    'image_prompt' => $scene->image_prompt,

                    /*
                    |--------------------------------------------------------------------------
                    | Composition
                    |--------------------------------------------------------------------------
                    */

                    'character_role' => $scene->character_role,

                    'shot_type' => $scene->shot_type,

                    /*
                    |--------------------------------------------------------------------------
                    | References
                    |--------------------------------------------------------------------------
                    */

                    'uses_character_reference' => !empty($references),
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

    /**
     * Construye las referencias que deben enviarse a FLUX
     * según el tipo de personajes utilizado por la escena.
     *
     * Las claves son semánticas para que FluxProvider pueda
     * saber qué representa cada imagen.
     */
    private function buildReferences(
        Scene $scene,
        Project $project
    ): array {
        $references = [];

        $characterRole = $scene->character_role;

        /*
        |--------------------------------------------------------------------------
        | DETECTIVE
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $characterRole,
                [
                    'detective',
                    'detective_and_generic_stickmen',
                ],
                true
            )
        ) {
            if ($project->character?->reference_sheet_path) {
                $references['detective_reference_sheet'] =
                    $project->character->reference_sheet_path;
            }

            if ($project->character?->portrait_reference_path) {
                $references['detective_portrait'] =
                    $project->character->portrait_reference_path;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | GENERIC STICKMEN
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $characterRole,
                [
                    'generic_stickmen',
                    'detective_and_generic_stickmen',
                ],
                true
            )
        ) {
            $genericReference = config(
                'youtube_studio.flux.generic_stickman_reference_path'
            );

            if ($genericReference) {
                $references['generic_stickman'] =
                    $genericReference;
            }
        }

        return $references;
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
