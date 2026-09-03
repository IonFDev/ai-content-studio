<?php
namespace App\Contracts\Transcription;
use App\Models\Project;
interface TranscriptionProvider { public function transcribe(Project $project): string; }
