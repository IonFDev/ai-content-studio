<?php
namespace App\Services\AI;
use App\Contracts\AI\AIProvider;
use App\Models\Project;
use RuntimeException;
class ClaudeProvider implements AIProvider {
 public function generateContent(Project $project, string $transcript): array { throw new RuntimeException('ClaudeProvider is intentionally disabled in the MVP. Add the API integration when the real provider is enabled.'); }
}
