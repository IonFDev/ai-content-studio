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

    public const CHARACTER_ROLES = [
        'none',
        'generic_stickmen',
        'detective',
        'detective_and_generic_stickmen',
    ];

    public const SHOT_TYPES = [
        'wide_establishing',
        'wide',
        'medium_wide',
        'medium',
        'close_up',
        'extreme_close_up',
        'top_down',
        'low_angle',
    ];

    protected $fillable = [
        'project_id',
        'order',
        'narration',

        // Dirección visual
        'visual_description',
        'character_role',
        'shot_type',
        'visual_metaphor',
        'visual_priority',

        // Generación
        'image_prompt',
        'image_path',
        'image_status',

        // Producción
        'manual_elements',
        'animation_notes',
        'production_notes',
    ];

    protected function casts(): array
    {
        return [
            'manual_elements' => 'array',
            'visual_priority' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}