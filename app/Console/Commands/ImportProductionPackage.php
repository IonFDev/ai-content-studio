<?php

namespace App\Console\Commands;

use App\Models\Character;
use App\Models\Project;
use App\Services\ProductionPackageImportService;
use Illuminate\Console\Command;
use Throwable;

class ImportProductionPackage extends Command
{
    protected $signature = 'youtube:import
                            {zip : Ruta al archivo ZIP}
                            {--character= : ID del personaje existente en este equipo}';

    protected $description = 'Importa un paquete ZIP de producción como un nuevo proyecto';

    public function handle(
        ProductionPackageImportService $importService
    ): int {
        $zipPath = $this->argument('zip');

        /*
        |--------------------------------------------------------------------------
        | Resolver ruta
        |--------------------------------------------------------------------------
        */

        $resolvedPath = $this->resolvePath(
            $zipPath
        );

        if (!is_file($resolvedPath)) {
            $this->error(
                "No existe el ZIP: {$resolvedPath}"
            );

            return self::FAILURE;
        }

        /*
        |--------------------------------------------------------------------------
        | Personaje
        |--------------------------------------------------------------------------
        */

        $characterOption = $this->option('character');

        $characterId = null;

        if ($characterOption !== null) {
            $characterId = (int) $characterOption;

            if (
                $characterId <= 0
                || !Character::whereKey($characterId)->exists()
            ) {
                $this->error(
                    "No existe el personaje con ID {$characterId}."
                );

                return self::FAILURE;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Importación
        |--------------------------------------------------------------------------
        */

        try {
            $this->info(
                'Importando paquete de producción...'
            );

            $project = $importService->import(
                zipPath: $resolvedPath,
                characterId: $characterId
            );

            $this->newLine();

            $this->info(
                'Proyecto importado correctamente.'
            );

            $this->line(
                "ID nuevo: {$project->id}"
            );

            $this->line(
                "Nombre: {$project->name}"
            );

            $this->line(
                "Estado: {$project->status}"
            );

            $this->line(
                "Escenas: {$project->scenes()->count()}"
            );

            $generatedImages = $project
                ->scenes()
                ->where(
                    'image_status',
                    'generated'
                )
                ->count();

            $this->line(
                "Imágenes: {$generatedImages}/"
                . $project->scenes()->count()
            );

            if (
                $characterId === null
                && $project->scenes()
                    ->whereIn(
                        'character_role',
                        [
                            'detective',
                            'detective_and_generic_stickmen',
                        ]
                    )
                    ->exists()
            ) {
                $this->newLine();

                $this->warn(
                    'El proyecto contiene escenas con Detective Stickman '
                    . 'pero no se ha asignado ningún Character.'
                );

                $this->warn(
                    'Usa --character=ID para asociar el personaje del equipo nuevo.'
                );
            }

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->newLine();

            $this->error(
                'No se pudo importar el proyecto.'
            );

            $this->error(
                $e->getMessage()
            );

            report($e);

            return self::FAILURE;
        }
    }

    private function resolvePath(
        string $path
    ): string {
        $path = trim($path);

        if (
            str_starts_with($path, '/')
            || preg_match(
                '/^[A-Za-z]:[\\\\\/]/',
                $path
            )
        ) {
            return $path;
        }

        return base_path($path);
    }
}
