<?php
namespace App\Jobs;
use App\Models\Project;
use App\Services\AIContentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
class GenerateContentJob implements ShouldQueue { use Dispatchable, Queueable; public function __construct(public int $projectId) {} public function handle(AIContentService $service): void { $service->generate(Project::findOrFail($this->projectId)); } }
