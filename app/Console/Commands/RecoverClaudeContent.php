<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\AIContentService;
use Illuminate\Console\Command;
use Throwable;

class RecoverClaudeContent extends Command
{
    protected $signature = 'youtube:recover-claude {project : ID del proyecto que debe recuperarse}';
    protected $description = 'Recupera la última respuesta válida de Claude desde claude-response.txt';
    public function handle(AIContentService $contentService): int
    {
        $projectId = (int) $this->argument('project');
        $project = Project::find($projectId);
        if (!$project) {
            $this->error("No existe el proyecto con ID {$projectId}.");
            return self::FAILURE;
        }
        $this->info("Recuperando contenido de Claude para el proyecto #{$project->id}...");
        try {
            $project = $contentService->recoverFromClaudeLog($project);
            $this->newLine();
            $this->info('Recuperación completada.');
            $this->line("Proyecto: {$project->name}");
            $this->line("Estado: {$project->status}");
            $this->line("Escenas: {$project->scenes()->count()}");
            $this->line("Título: {$project->youtube_title}");
            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->newLine();
            $this->error('No se pudo recuperar el contenido.');
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}
