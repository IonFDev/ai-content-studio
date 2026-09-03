<?php
namespace App\Services;
use App\Contracts\AI\AIProvider;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
class AIContentService {
 public function __construct(private readonly AIProvider $provider) {}
 public function generate(Project $project): Project { if(blank($project->transcript)) throw new \RuntimeException('A transcript is required before generating content.'); $data=$this->provider->generateContent($project,$project->transcript); DB::transaction(function() use($project,$data){ $project->update(['youtube_title'=>$data['youtube_title']??null,'youtube_description'=>$data['youtube_description']??null,'youtube_keywords'=>$data['youtube_keywords']??[],'youtube_hashtags'=>$data['youtube_hashtags']??[],'thumbnail_idea'=>$data['thumbnail_idea']??null,'thumbnail_text'=>$data['thumbnail_text']??null,'script'=>$data['script']??null,'status'=>'script_ready']); Storage::disk('local')->put("projects/{$project->id}/storyboard/content.json", json_encode(['scenes'=>$data['scenes']??[]], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)); }); return $project->refresh(); }
}
