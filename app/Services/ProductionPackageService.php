<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class ProductionPackageService
{
    public function create(Project $project): string
    {
        $project->loadMissing([
            'character',
            'scenes',
        ]);

        $disk = Storage::disk('local');

        $projectPath = $disk->path(
            "projects/{$project->id}"
        );

        if (!is_dir($projectPath)) {
            throw new RuntimeException(
                "No existe la carpeta de producción del proyecto {$project->id}."
            );
        }

        $packagesDirectory = $disk->path('packages');

        File::ensureDirectoryExists($packagesDirectory);

        $projectSlug = Str::slug(
            trim((string) $project->name)
        );

        if ($projectSlug === '') {
            $projectSlug = "project-{$project->id}";
        }

        $filename = "{$projectSlug}-{$project->id}.zip";

        $zipPath = $packagesDirectory . DIRECTORY_SEPARATOR . $filename;

        if (is_file($zipPath)) {
            File::delete($zipPath);
        }

        $zip = new ZipArchive();

        $result = $zip->open(
            $zipPath,
            ZipArchive::CREATE | ZipArchive::OVERWRITE
        );

        if ($result !== true) {
            throw new RuntimeException(
                "No se pudo crear el ZIP. Código ZipArchive: {$result}."
            );
        }

        $rootFolder = $projectSlug;

        /*
        |--------------------------------------------------------------------------
        | Archivos del proyecto
        |--------------------------------------------------------------------------
        */

        $this->addDirectoryToZip(
            zip: $zip,
            directory: $projectPath,
            zipPrefix: $rootFolder
        );

        /*
        |--------------------------------------------------------------------------
        | Snapshot del proyecto
        |--------------------------------------------------------------------------
        */

        $projectData = [
            'id' => $project->id,
            'name' => $project->name,
            'source_url' => $project->source_url,
            'source_title' => $project->source_title,
            'status' => $project->status,

            'youtube' => [
                'title' => $project->youtube_title,
                'description' => $project->youtube_description,
                'keywords' => $project->youtube_keywords,
                'hashtags' => $project->youtube_hashtags,
                'thumbnail_idea' => $project->thumbnail_idea,
                'thumbnail_text' => $project->thumbnail_text,
            ],

            'created_at' => $project->created_at?->toISOString(),
            'updated_at' => $project->updated_at?->toISOString(),
        ];

        $zip->addFromString(
            "{$rootFolder}/project.json",
            json_encode(
                $projectData,
                JSON_PRETTY_PRINT
                | JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Snapshot de escenas
        |--------------------------------------------------------------------------
        */

        $scenes = $project->scenes
            ->sortBy('order')
            ->values()
            ->map(
                fn ($scene) => [
                    'id' => $scene->id,
                    'order' => $scene->order,
                    'narration' => $scene->narration,
                    'visual_concept' => $scene->visual_concept,
                    'visual_description' => $scene->visual_description,
                    'visual_metaphor' => $scene->visual_metaphor,
                    'character_role' => $scene->character_role,
                    'shot_type' => $scene->shot_type,
                    'image_prompt' => $scene->image_prompt,
                    'image_path' => $scene->image_path,
                    'image_status' => $scene->image_status,
                    'manual_elements' => $scene->manual_elements,
                    'animation_notes' => $scene->animation_notes,
                    'production_notes' => $scene->production_notes,
                ]
            )
            ->all();

        $zip->addFromString(
            "{$rootFolder}/scenes.json",
            json_encode(
                $scenes,
                JSON_PRETTY_PRINT
                | JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Manifest
        |--------------------------------------------------------------------------
        */

        $generatedImages = $project->scenes
            ->where('image_status', 'generated')
            ->count();

        $totalScenes = $project->scenes->count();

        $manifest = implode("\n", [
            'STICKMAN CASEBOOK — PRODUCTION PACKAGE',
            '==========================================',
            '',
            "Project ID: {$project->id}",
            "Project name: {$project->name}",
            "Status: {$project->status}",
            '',
            "Scenes: {$totalScenes}",
            "Generated images: {$generatedImages}",
            "Pending images: " . max(0, $totalScenes - $generatedImages),
            '',
            'CONTENTS',
            '--------',
            'source/',
            'transcript/',
            'script/',
            'storyboard/',
            'images/',
            'audio/',
            'production/',
            'project.json',
            'scenes.json',
            'MANIFEST.txt',
            '',
            'Generated by YouTube Studio.',
        ]);

        $zip->addFromString(
            "{$rootFolder}/MANIFEST.txt",
            $manifest
        );

        /*
        |--------------------------------------------------------------------------
        | Cerrar ZIP
        |--------------------------------------------------------------------------
        */

        if (!$zip->close()) {
            throw new RuntimeException(
                'No se pudo finalizar correctamente el archivo ZIP.'
            );
        }

        if (!is_file($zipPath)) {
            throw new RuntimeException(
                'El ZIP se cerró correctamente pero no se encontró el archivo generado.'
            );
        }

        return $zipPath;
    }

    private function addDirectoryToZip(
        ZipArchive $zip,
        string $directory,
        string $zipPrefix
    ): void {
        if (!is_dir($directory)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $directory,
                \FilesystemIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $absolutePath = $file->getRealPath();

            if ($absolutePath === false) {
                continue;
            }

            $relativePath = ltrim(
                str_replace(
                    $directory,
                    '',
                    $absolutePath
                ),
                DIRECTORY_SEPARATOR . '/\\'
            );

            $relativePath = str_replace(
                DIRECTORY_SEPARATOR,
                '/',
                $relativePath
            );

            if ($relativePath === '') {
                continue;
            }

            $zipPath = "{$zipPrefix}/{$relativePath}";

            $zip->addFile(
                $absolutePath,
                $zipPath
            );
        }
    }
}