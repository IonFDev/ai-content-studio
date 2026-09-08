<?php

namespace App\Jobs;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class GenerateAllImagesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(
        public int $projectId
    ) {
    }

    public function handle(): void
    {
        $project = Project::findOrFail($this->projectId);

        $scenes = $project->scenes()
            ->orderBy('order')
            ->get();

        if ($scenes->isEmpty()) {
            $project->update([
                'status' => 'error',
            ]);

            throw new \RuntimeException(
                'El proyecto no tiene escenas para generar.'
            );
        }

        $project->update([
            'status' => 'images_generating',
        ]);

        foreach ($scenes as $scene) {
            if ($scene->image_status === 'generated') {
                continue;
            }

            GenerateSceneImageJob::dispatch(
                $scene->id
            );
        }
    }

    public function failed(Throwable $exception): void
    {
        Project::whereKey($this->projectId)->update([
            'status' => 'error',
        ]);
    }
}