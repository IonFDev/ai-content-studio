<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Scene;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

class ProductionPackageImportService
{
    private const REQUIRED_FILES = [
        'project.json',
        'scenes.json',
        'MANIFEST.txt',
    ];

    public function import(
        string $zipPath,
        ?int $characterId = null
    ): Project {
        if (!is_file($zipPath)) {
            throw new RuntimeException(
                "No existe el archivo ZIP: {$zipPath}"
            );
        }

        $zip = new ZipArchive();

        $result = $zip->open($zipPath);

        if ($result !== true) {
            throw new RuntimeException(
                "No se pudo abrir el ZIP. Código: {$result}."
            );
        }

        try {
            $this->validateZipEntries($zip);

            $files = $this->locatePackageFiles($zip);

            $projectData = $this->readJsonEntry(
                $zip,
                $files['project']
            );

            $scenesData = $this->readJsonEntry(
                $zip,
                $files['scenes']
            );

            $this->validateProjectData($projectData);
            $this->validateScenesData($scenesData);

            $temporaryDirectory = $this->createTemporaryDirectory();

            try {
                $zip->extractTo($temporaryDirectory);

                $sourceProjectDirectory =
                    $this->locateExtractedProjectDirectory(
                        $temporaryDirectory,
                        $files['project']
                    );

                return $this->restoreProject(
                    projectData: $projectData,
                    scenesData: $scenesData,
                    extractedProjectDirectory: $sourceProjectDirectory,
                    characterId: $characterId
                );
            } finally {
                File::deleteDirectory(
                    $temporaryDirectory
                );
            }
        } finally {
            $zip->close();
        }
    }

    private function restoreProject(
        array $projectData,
        array $scenesData,
        string $extractedProjectDirectory,
        ?int $characterId
    ): Project {
        $disk = Storage::disk('local');

        $project = DB::transaction(function () use (
            $projectData,
            $scenesData,
            $extractedProjectDirectory,
            $characterId,
            $disk
        ) {
            $script = $this->extractScript(
                $projectData,
                $extractedProjectDirectory
            );

            $transcript = $this->extractTranscript(
                $extractedProjectDirectory
            );

            $project = Project::create([
                'character_id' => $characterId,
                'name' => $projectData['name'] ?? 'Imported project',
                'source_url' => $projectData['source_url'] ?? null,
                'source_title' => $projectData['source_title'] ?? null,
                'status' => $projectData['status'] ?? 'draft',
                'transcript' => $transcript,
                'script' => $script,

                'youtube_title' =>
                    $projectData['youtube']['title'] ?? null,

                'youtube_description' =>
                    $projectData['youtube']['description'] ?? null,

                'youtube_keywords' =>
                    $projectData['youtube']['keywords'] ?? [],

                'youtube_hashtags' =>
                    $projectData['youtube']['hashtags'] ?? [],

                'thumbnail_idea' =>
                    $projectData['youtube']['thumbnail_idea'] ?? null,

                'thumbnail_text' =>
                    $projectData['youtube']['thumbnail_text'] ?? null,
            ]);

            $targetDirectory =
                $disk->path(
                    "projects/{$project->id}"
                );

            File::ensureDirectoryExists(
                $targetDirectory
            );

            /*
            |--------------------------------------------------------------------------
            | Copiar archivos del proyecto
            |--------------------------------------------------------------------------
            */

            $this->copyProjectFiles(
                sourceDirectory: $extractedProjectDirectory,
                targetDirectory: $targetDirectory
            );

            /*
            |--------------------------------------------------------------------------
            | Restaurar escenas
            |--------------------------------------------------------------------------
            */

            foreach ($scenesData as $index => $sceneData) {
                if (!is_array($sceneData)) {
                    continue;
                }

                $order = (int) (
                    $sceneData['order']
                    ?? ($index + 1)
                );

                $imagePath = $this->restoreImagePath(
                    project: $project,
                    sceneData: $sceneData
                );

                $imageStatus = $this->resolveImageStatus(
                    project: $project,
                    imagePath: $imagePath,
                    originalStatus: $sceneData['image_status'] ?? 'pending'
                );

                $project->scenes()->create([
                    'order' => $order,

                    'narration' => trim(
                        (string) (
                            $sceneData['narration'] ?? ''
                        )
                    ),

                    'visual_concept' =>
                        $sceneData['visual_concept'] ?? null,

                    'visual_description' =>
                        $sceneData['visual_description'] ?? null,

                    'character_role' =>
                        $this->normalizeCharacterRole(
                            $sceneData['character_role'] ?? 'none'
                        ),

                    'shot_type' =>
                        $this->normalizeShotType(
                            $sceneData['shot_type'] ?? 'medium'
                        ),

                    'visual_metaphor' =>
                        $sceneData['visual_metaphor'] ?? null,

                    'visual_priority' =>
                        $sceneData['visual_priority'] ?? null,

                    'image_prompt' =>
                        $sceneData['image_prompt'] ?? null,

                    'image_path' => $imagePath,

                    'image_status' => $imageStatus,

                    'manual_elements' =>
                        is_array(
                            $sceneData['manual_elements'] ?? null
                        )
                            ? $sceneData['manual_elements']
                            : [],

                    'animation_notes' =>
                        $sceneData['animation_notes'] ?? null,

                    'production_notes' =>
                        $sceneData['production_notes'] ?? null,
                ]);
            }

            return $project->refresh();
        });

        return $project->load('scenes');
    }

    private function copyProjectFiles(
        string $sourceDirectory,
        string $targetDirectory
    ): void {
        if (!is_dir($sourceDirectory)) {
            throw new RuntimeException(
                'No se encontró la carpeta interna del proyecto dentro del ZIP.'
            );
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $sourceDirectory,
                \FilesystemIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = ltrim(
                str_replace(
                    $sourceDirectory,
                    '',
                    $item->getPathname()
                ),
                DIRECTORY_SEPARATOR . '/\\'
            );

            if ($relativePath === '') {
                continue;
            }

            $relativePath = str_replace(
                DIRECTORY_SEPARATOR,
                '/',
                $relativePath
            );

            /*
             * Los metadatos del ZIP se reconstruyen en DB.
             * No necesitamos copiarlos a la carpeta del proyecto.
             */
            if (
                $relativePath === 'project.json'
                || $relativePath === 'scenes.json'
                || $relativePath === 'MANIFEST.txt'
            ) {
                continue;
            }

            $destination = $targetDirectory
                . DIRECTORY_SEPARATOR
                . str_replace(
                    '/',
                    DIRECTORY_SEPARATOR,
                    $relativePath
                );

            if ($item->isDir()) {
                File::ensureDirectoryExists(
                    $destination
                );

                continue;
            }

            File::ensureDirectoryExists(
                dirname($destination)
            );

            File::copy(
                $item->getPathname(),
                $destination
            );
        }
    }

    private function restoreImagePath(
        Project $project,
        array $sceneData
    ): ?string {
        $originalPath = $sceneData['image_path'] ?? null;

        if (
            !is_string($originalPath)
            || trim($originalPath) === ''
        ) {
            return null;
        }

        $filename = basename($originalPath);

        if ($filename === '' || $filename === '.') {
            return null;
        }

        $newPath =
            "projects/{$project->id}/images/{$filename}";

        if (
            Storage::disk('local')->exists($newPath)
        ) {
            return $newPath;
        }

        return null;
    }

    private function resolveImageStatus(
        Project $project,
        ?string $imagePath,
        mixed $originalStatus
    ): string {
        if (
            $imagePath !== null
            && Storage::disk('local')->exists($imagePath)
        ) {
            return 'generated';
        }

        if (
            in_array(
                $originalStatus,
                [
                    'pending',
                    'generating',
                    'error',
                ],
                true
            )
        ) {
            return $originalStatus;
        }

        return 'pending';
    }

    private function extractScript(
        array $projectData,
        string $projectDirectory
    ): ?string {
        $contentJsonPath =
            $projectDirectory
            . DIRECTORY_SEPARATOR
            . 'storyboard'
            . DIRECTORY_SEPARATOR
            . 'content.json';

        if (is_file($contentJsonPath)) {
            $content = json_decode(
                (string) file_get_contents(
                    $contentJsonPath
                ),
                true
            );

            if (
                is_array($content)
                && isset($content['content']['script'])
                && is_string($content['content']['script'])
            ) {
                return trim(
                    $content['content']['script']
                );
            }
        }

        if (
            isset($projectData['script'])
            && is_string($projectData['script'])
        ) {
            return trim($projectData['script']);
        }

        return null;
    }

    private function extractTranscript(
        string $projectDirectory
    ): ?string {
        $transcriptDirectory =
            $projectDirectory
            . DIRECTORY_SEPARATOR
            . 'transcript';

        if (!is_dir($transcriptDirectory)) {
            return null;
        }

        $files = glob(
            $transcriptDirectory
            . DIRECTORY_SEPARATOR
            . '*'
        );

        if (!$files) {
            return null;
        }

        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }

            $extension = strtolower(
                pathinfo(
                    $file,
                    PATHINFO_EXTENSION
                )
            );

            if (
                in_array(
                    $extension,
                    ['txt', 'text'],
                    true
                )
            ) {
                $contents = file_get_contents($file);

                if (
                    $contents !== false
                    && trim($contents) !== ''
                ) {
                    return trim($contents);
                }
            }
        }

        return null;
    }

    private function validateProjectData(
        array $data
    ): void {
        if (
            !isset($data['name'])
            || !is_string($data['name'])
            || trim($data['name']) === ''
        ) {
            throw new RuntimeException(
                'project.json no contiene un nombre de proyecto válido.'
            );
        }

        if (
            isset($data['youtube'])
            && !is_array($data['youtube'])
        ) {
            throw new RuntimeException(
                'project.json contiene una sección youtube inválida.'
            );
        }
    }

    private function validateScenesData(
        array $data
    ): void {
        if (empty($data)) {
            throw new RuntimeException(
                'scenes.json no contiene escenas.'
            );
        }

        foreach ($data as $index => $scene) {
            if (!is_array($scene)) {
                throw new RuntimeException(
                    'scenes.json contiene una escena inválida en la posición '
                    . ($index + 1)
                    . '.'
                );
            }

            if (
                !isset($scene['order'])
                || !is_numeric($scene['order'])
            ) {
                throw new RuntimeException(
                    'scenes.json contiene una escena sin order válido.'
                );
            }
        }
    }

    private function readJsonEntry(
        ZipArchive $zip,
        string $entry
    ): array {
        $contents = $zip->getFromName($entry);

        if ($contents === false) {
            throw new RuntimeException(
                "No se pudo leer '{$entry}' del ZIP."
            );
        }

        $data = json_decode(
            $contents,
            true
        );

        if (!is_array($data)) {
            throw new RuntimeException(
                "El archivo '{$entry}' no contiene JSON válido."
            );
        }

        return $data;
    }

    private function locatePackageFiles(
        ZipArchive $zip
    ): array {
        $projectJson = null;
        $scenesJson = null;
        $manifest = null;

        for (
            $index = 0;
            $index < $zip->numFiles;
            $index++
        ) {
            $name = $zip->getNameIndex($index);

            if ($name === false) {
                continue;
            }

            $normalized = ltrim(
                str_replace('\\', '/', $name),
                '/'
            );

            $basename = basename($normalized);

            if ($basename === 'project.json') {
                $projectJson = $normalized;
            }

            if ($basename === 'scenes.json') {
                $scenesJson = $normalized;
            }

            if ($basename === 'MANIFEST.txt') {
                $manifest = $normalized;
            }
        }

        foreach ([
            'project' => $projectJson,
            'scenes' => $scenesJson,
            'manifest' => $manifest,
        ] as $type => $path) {
            if ($path === null) {
                throw new RuntimeException(
                    "El ZIP no contiene {$type}."
                );
            }
        }

        return [
            'project' => $projectJson,
            'scenes' => $scenesJson,
            'manifest' => $manifest,
        ];
    }

    private function locateExtractedProjectDirectory(
        string $temporaryDirectory,
        string $projectJsonEntry
    ): string {
        $parts = explode(
            '/',
            trim(
                str_replace(
                    '\\',
                    '/',
                    $projectJsonEntry
                ),
                '/'
            )
        );

        if (count($parts) < 2) {
            throw new RuntimeException(
                'La estructura interna del ZIP no es válida.'
            );
        }

        $rootFolder = $parts[0];

        $projectDirectory =
            $temporaryDirectory
            . DIRECTORY_SEPARATOR
            . $rootFolder;

        if (!is_dir($projectDirectory)) {
            throw new RuntimeException(
                'No se encontró la carpeta principal del proyecto dentro del ZIP.'
            );
        }

        return $projectDirectory;
    }

    private function createTemporaryDirectory(): string
    {
        $directory = storage_path(
            'app/imports/.tmp-' . bin2hex(
                random_bytes(8)
            )
        );

        File::ensureDirectoryExists(
            $directory
        );

        return $directory;
    }

    private function validateZipEntries(
        ZipArchive $zip
    ): void {
        for (
            $index = 0;
            $index < $zip->numFiles;
            $index++
        ) {
            $name = $zip->getNameIndex($index);

            if ($name === false) {
                continue;
            }

            $normalized = str_replace(
                '\\',
                '/',
                $name
            );

            if (
                str_starts_with(
                    $normalized,
                    '/'
                )
                || preg_match(
                    '#(^|/)\.\.(/|$)#',
                    $normalized
                )
            ) {
                throw new RuntimeException(
                    'El ZIP contiene una ruta no segura: '
                    . $name
                );
            }
        }
    }

    private function normalizeCharacterRole(
        mixed $role
    ): string {
        return in_array(
            $role,
            Scene::CHARACTER_ROLES,
            true
        )
            ? $role
            : 'none';
    }

    private function normalizeShotType(
        mixed $shotType
    ): string {
        return in_array(
            $shotType,
            Scene::SHOT_TYPES,
            true
        )
            ? $shotType
            : 'medium';
    }
}
