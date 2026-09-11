<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Scene extends Model
{
    use HasFactory;

    public const IMAGE_STATUSES = [
        'pending',
        'generating',
        'generated',
        'error',
    ];

    protected $fillable = [
        'project_id',
        'order',
        'narration',
        'visual_description',
        'image_prompt',
        'image_path',
        'image_status',
        'manual_elements',
        'animation_notes',
        'production_notes',
    ];

    protected function casts(): array
    {
        return [
            'manual_elements' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
