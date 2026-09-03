<?php
namespace App\Services;
use App\Contracts\Transcription\TranscriptionProvider;
use App\Models\Project;
class TranscriptionService {
 public function __construct(private readonly TranscriptionProvider $provider) {}
 public function transcribe(Project $project): Project { $text=$this->provider->transcribe($project); $project->update(['transcript'=>$text,'status'=>'transcript_ready']); return $project->refresh(); }
}
