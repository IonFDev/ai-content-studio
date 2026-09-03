<?php
namespace App\Services;
use App\Contracts\Images\ImageProvider;
use App\Models\Project;
use App\Models\Scene;
use Illuminate\Support\Facades\Storage;
class ImageGenerationService {
 public function __construct(private readonly ImageProvider $provider) {}
 public function generate(Scene $scene): Scene {
  $project=$scene->project()->with('character')->firstOrFail();
  $scene->update(['image_status'=>'generating']);
  try {
   $references=[];
   if($project->character?->reference_sheet_path) $references[]=$project->character->reference_sheet_path;
   if($project->character?->portrait_reference_path) $references[]=$project->character->portrait_reference_path;
   $result=$this->provider->generateImage($scene,$references);
   $path="projects/{$project->id}/images/".str_pad((string)$scene->order,3,'0',STR_PAD_LEFT).'.'.($result['extension']??'png');
   Storage::disk('local')->put($path,$result['contents']);
   $scene->update(['image_path'=>$path,'image_status'=>'generated']);
   $this->syncProjectStatus($project->refresh());
   return $scene->refresh();
  } catch(\Throwable $e) { $scene->update(['image_status'=>'error']); $project->update(['status'=>'error']); throw $e; }
 }
 public function generateAll(Project $project): void { $project->update(['status'=>'images_generating']); foreach($project->scenes()->get() as $scene) $this->generate($scene); $project->refresh(); if($project->scenes()->exists() && $project->scenes()->where('image_status','!=','generated')->doesntExist()) $project->update(['status'=>'images_ready']); }
 private function syncProjectStatus(Project $project): void { $total=$project->scenes()->count(); $generated=$project->scenes()->where('image_status','generated')->count(); if($total>0 && $generated===$total) $project->update(['status'=>'images_ready']); else $project->update(['status'=>'images_generating']); }
}
