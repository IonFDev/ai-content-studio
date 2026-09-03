<?php
namespace App\Services;
use App\Models\Project;
use Illuminate\Support\Facades\Storage;
class ProjectService {
 public function create(array $data): Project { $project=Project::create($data+['status'=>'source_ready']); $this->ensureDirectories($project); return $project; }
 public function ensureDirectories(Project $project): void { foreach(['source','transcript','script','storyboard','images','assets'] as $folder) Storage::disk('local')->makeDirectory("projects/{$project->id}/{$folder}"); }
 public function sourceReady(Project $project): void { if($project->source_url && $project->status==='draft'){ $project->update(['status'=>'source_ready']); } }
}
