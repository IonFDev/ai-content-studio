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
];
