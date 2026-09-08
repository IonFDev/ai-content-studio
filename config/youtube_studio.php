<?php

return [
    'providers' => [
        'ai' => env('AI_PROVIDER', 'fake'),
        'image' => env('IMAGE_PROVIDER', 'fake'),
        'transcription' => env('TRANSCRIPTION_PROVIDER', 'fake'),
    ],

    'whisper' => [
        'url' => env('WHISPER_SERVICE_URL', 'http://whisper:8000'),
        'timeout' => (int) env('WHISPER_TIMEOUT', 3600),
        'language' => env('WHISPER_LANGUAGE', null),
    ],

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-4-6'),
        'timeout' => (int) env('ANTHROPIC_TIMEOUT', 900),
    ],

    'ideogram' => [
        'api_key' => env('IDEOGRAM_API_KEY'),
        'model' => env('IDEOGRAM_MODEL', 'ideogram-v3'),
        'timeout' => (int) env('IDEOGRAM_TIMEOUT', 300),

        'aspect_ratio' => env('IDEOGRAM_ASPECT_RATIO', '16x9'),
        'rendering_speed' => env('IDEOGRAM_RENDERING_SPEED', 'TURBO'),
        'magic_prompt' => env('IDEOGRAM_MAGIC_PROMPT', 'OFF'),

        'character_reference_path' => env(
            'IDEOGRAM_CHARACTER_REFERENCE_PATH',
            'characters/detective-stickman/character-reference.png'
        ),
    ],
];