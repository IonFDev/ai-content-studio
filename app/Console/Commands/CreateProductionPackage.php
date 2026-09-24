<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\ProductionPackageService;
use Illuminate\Console\Command;
use Throwable;

class CreateProductionPackage extends Command
{
    protected $signature = 'youtube:package
                            {project : ID del proyecto}';

    protected $description = 'Genera el ZIP completo de producción de un proyecto';

    public function handle(
        ProductionPackageService $packageService
    ): int {
        $projectId = (int) $this->argument('project');

        $project = Project::find($projectId);

        if (!$project) {
            $this->error(
                "No existe el proyecto con ID {$projectId}."
            );

            return self::FAILURE;
        }

        try {
            $this->info(
                "Generando paquete de producción para el proyecto #{$projectId}..."
            );

            $zipPath = $packageService->create($project);

            $sizeMb = number_format(
                filesize($zipPath) / 1024 / 1024,
                2
            );

            $this->newLine();

            $this->info('Paquete creado correctamente.');

            $this->line(
                "Archivo: {$zipPath}"
            );

            $this->line(
                "Tamaño: {$sizeMb} MB"
            );

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->newLine();

            $this->error(
                'No se pudo generar el paquete.'
            );

            $this->error(
                $e->getMessage()
            );

            return self::FAILURE;
        }
    }
}