<?php

namespace App\Contracts\Voice;

interface VoiceProvider
{
    /**
     * @return array{
     *     audio: string,
     *     alignment: array{
     *         characters: array<int, string>,
     *         character_start_times_seconds: array<int, float>,
     *         character_end_times_seconds: array<int, float>
     *     }
     * }
     */
    public function generate(string $text): array;
}