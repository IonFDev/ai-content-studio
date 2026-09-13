<?php

namespace App\Jobs;

use App\Models\Scene;
use App\Services\ImageGenerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class GenerateSceneImageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    public function __construct(
        public int $sceneId
    ) {
    }

    public function handle(
        ImageGenerationService $imageGenerationService
    ): void {
        $scene = Scene::with('project')->findOrFail($this->sceneId);

        if ($scene->image_status === 'generated') {
            return;
        }

        try {
            $imageGenerationService->generate($scene);
        } catch (Throwable $e) {
            $scene->update([
                'image_status' => 'error',
            ]);

            throw $e;
        }
    }

    private function updateProjectStatus(Scene $scene): void
    {
        $project = $scene->project;

        if (!$project) {
            return;
        }

        $total = $project->scenes()->count();

        $generated = $project->scenes()
            ->where('image_status', 'generated')
            ->count();

        $errors = $project->scenes()
            ->where('image_status', 'error')
            ->count();

        if ($total > 0 && $generated === $total) {
            $project->update([
                'status' => 'images_ready',
            ]);

            return;
        }

        if ($errors > 0) {
            $project->update([
                'status' => 'error',
            ]);

            return;
        }

        $project->update([
            'status' => 'images_generating',
        ]);
    }

    public function failed(Throwable $exception): void
    {
        $scene = Scene::with('project')->find($this->sceneId);

        if (!$scene) {
            return;
        }

        $scene->update([
            'image_status' => 'error',
        ]);

        $scene->project?->update([
            'status' => 'error',
        ]);
    }
}
