<?php
namespace App\Jobs;
use App\Models\Project;
use App\Services\ImageGenerationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
class GenerateAllImagesJob implements ShouldQueue { use Dispatchable, Queueable; public function __construct(public int $projectId) {} public function handle(ImageGenerationService $service): void { $service->generateAll(Project::findOrFail($this->projectId)); } }
