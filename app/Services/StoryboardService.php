<?php
namespace App\Services;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
class StoryboardService {

 public function generate(Project $project): Project {
  $path="projects/{$project->id}/storyboard/content.json";
  if(!Storage::disk('local')->exists($path)) throw new \RuntimeException('Generate content before generating the storyboard.');
  $data=json_decode(Storage::disk('local')->get($path),true) ?: [];
  DB::transaction(function() use($project,$data){
   $project->scenes()->delete();
   foreach($data['scenes']??[] as $scene){ $project->scenes()->create(['order'=>(int)$scene['order'],'narration'=>$scene['narration'],'visual_description'=>$scene['visual_description']??null,'image_prompt'=>$scene['image_prompt']??null,'image_status'=>'pending']); }
   $project->update(['status'=>'storyboard_ready']);
  });
  return $project->refresh();
 }
 public function reorder(Project $project,array $orders): void { DB::transaction(function() use($project,$orders){ foreach($orders as $index=>$sceneId){ $project->scenes()->whereKey($sceneId)->update(['order'=>$index+1]); } }); }
}
