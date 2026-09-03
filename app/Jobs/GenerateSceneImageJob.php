<?php
namespace App\Jobs;
use App\Models\Scene;
use App\Services\ImageGenerationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
class GenerateSceneImageJob implements ShouldQueue { use Dispatchable, Queueable; public function __construct(public int $sceneId) {} public function handle(ImageGenerationService $service): void { $service->generate(Scene::findOrFail($this->sceneId)); } }
