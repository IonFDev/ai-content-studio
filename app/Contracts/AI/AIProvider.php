<?php
namespace App\Contracts\AI;
use App\Models\Project;
interface AIProvider { public function generateContent(Project $project, string $transcript): array; }
