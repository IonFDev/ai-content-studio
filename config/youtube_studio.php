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
            'characters/detective-stickman/detective-stickman-closeup.png'
        ),

        'style_reference_path' => env(
            'IDEOGRAM_STYLE_REFERENCE_PATH',
            'characters/detective-stickman/detective-stickman-poses.png'
        ),
    ],

    'flux' => [
        'api_key' => env('BFL_API_KEY'),
        'model' => env('BFL_MODEL', 'flux-2-pro'),
        'timeout' => (int) env('BFL_TIMEOUT', 300),
        'poll_interval' => (int) env('BFL_POLL_INTERVAL', 1),
        'max_poll_attempts' => (int) env('BFL_MAX_POLL_ATTEMPTS', 120),
    ],

    'elevenlabs' => [
        'api_key' => env('ELEVENLABS_API_KEY'),
        'voice_id' => env('ELEVENLABS_VOICE_ID'),
        'model' => env('ELEVENLABS_MODEL', 'eleven_multilingual_v2'),
        'output_format' => env('ELEVENLABS_OUTPUT_FORMAT', 'mp3_44100_128'),
        'timeout' => (int) env('ELEVENLABS_TIMEOUT', 300),
    ],
];