<?php

namespace App\Services;

use App\Models\Project;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ProductionGuideService
{
    public function generate(Project $project)
    {
        $project->load([
            'character',
            'scenes',
        ]);

        $scenes = $project->scenes()
            ->orderBy('order')
            ->get();

        $scenes = $scenes->map(function ($scene) {
            $scene->image_data_uri = $this->getImageDataUri(
                $scene->image_path
            );

            return $scene;
        });

        return Pdf::loadView(
            'projects.production-guide',
            [
                'project' => $project,
                'scenes' => $scenes,
            ]
        )
        ->setPaper('a4', 'portrait');
    }

    private function getImageDataUri(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        $disk = Storage::disk('local');

        if (!$disk->exists($path)) {
            return null;
        }

        $contents = $disk->get($path);

        $mime = $disk->mimeType($path);

        if (!$mime) {
            return null;
        }

        return 'data:' . $mime . ';base64,' . base64_encode($contents);
    }

    public function productionGuide(
        Project $project,
        ProductionGuideService $service
    ) {
        $pdf = $service->generate($project);

        return $pdf->stream(
            'guia-produccion-' . $project->id . '.pdf'
        );
    }

}
