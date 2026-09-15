<?php

namespace App\Services;

use App\Contracts\Voice\VoiceProvider;
use App\Models\Project;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class VoiceGenerationService
{
    public function __construct(
        private VoiceProvider $provider
    ) {
    }

    public function generate(Project $project): array
    {
        $project->load([
            'scenes' => fn ($query) => $query->orderBy('order'),
        ]);

        $scenes = $project->scenes;

        if ($scenes->isEmpty()) {
            throw new RuntimeException(
                'El proyecto no tiene escenas.'
            );
        }

        $parts = [];
        $sceneOffsets = [];

        $currentOffset = 0;

        foreach ($scenes as $scene) {
            $narration = trim((string) $scene->narration);

            if ($narration === '') {
                throw new RuntimeException(
                    "La escena {$scene->order} no tiene narración."
                );
            }

            $start = $currentOffset;
            $length = mb_strlen($narration);

            $parts[] = $narration;

            $sceneOffsets[] = [
                'scene' => $scene,
                'start_char' => $start,
                'end_char' => $start + $length - 1,
            ];

            /*
             * Dos saltos de línea entre escenas.
             */
            $currentOffset += $length + 2;
        }

        $fullText = implode("\n\n", $parts);

        $result = $this->provider->generate($fullText);

        $audioPath = $this->saveAudio(
            $project,
            $result['audio']
        );

        $timing = $this->buildTiming(
            $sceneOffsets,
            $result['alignment']
        );

        $timingPath = $this->saveTiming(
            $project,
            $timing
        );

        return [
            'audio_path' => $audioPath,
            'timing_path' => $timingPath,
            'duration' => $timing['duration'],
            'scenes' => $timing['scenes'],
        ];
    }

    private function buildTiming(
        array $sceneOffsets,
        array $alignment
    ): array {
        $characters = $alignment['characters'];
        $starts = $alignment['character_start_times_seconds'];
        $ends = $alignment['character_end_times_seconds'];

        $alignmentCount = count($characters);

        if ($alignmentCount === 0) {
            throw new RuntimeException(
                'ElevenLabs ha devuelto un alignment vacío.'
            );
        }

        if (
            count($starts) !== $alignmentCount ||
            count($ends) !== $alignmentCount
        ) {
            throw new RuntimeException(
                'Las matrices de timestamps de ElevenLabs no tienen la misma longitud.'
            );
        }

        /*
         * Comprobamos que ElevenLabs realmente nos ha devuelto
         * el mismo número de caracteres que enviamos.
         *
         * Si no coincide, no inventamos timestamps.
         */
        $expectedCharacterCount = 0;

        foreach ($sceneOffsets as $offset) {
            $expectedCharacterCount +=
                $offset['end_char'] - $offset['start_char'] + 1;
        }

        /*
         * Entre escenas hay dos \n.
         *
         * Los caracteres de separación también forman parte
         * del texto enviado, por lo que:
         *
         * total = caracteres de narraciones + separadores
         */
        $separatorCount = max(
            0,
            count($sceneOffsets) - 1
        ) * 2;

        $expectedCharacterCount += $separatorCount;

        if ($alignmentCount !== $expectedCharacterCount) {
            throw new RuntimeException(
                sprintf(
                    'El alignment de ElevenLabs no coincide con el texto enviado. Esperados: %d caracteres. Recibidos: %d.',
                    $expectedCharacterCount,
                    $alignmentCount
                )
            );
        }

        $timedScenes = [];

        foreach ($sceneOffsets as $offset) {
            $startIndex = $offset['start_char'];
            $endIndex = $offset['end_char'];

            if (
                !isset($starts[$startIndex]) ||
                !isset($ends[$endIndex])
            ) {
                throw new RuntimeException(
                    "No se pudieron obtener timestamps para la escena {$offset['scene']->order}."
                );
            }

            $start = (float) $starts[$startIndex];
            $end = (float) $ends[$endIndex];

            if ($end < $start) {
                throw new RuntimeException(
                    "Los timestamps de la escena {$offset['scene']->order} son inválidos."
                );
            }

            $timedScenes[] = [
                'scene' => (int) $offset['scene']->order,
                'image' => sprintf(
                    'images/%03d.jpg',
                    $offset['scene']->order
                ),
                'start' => round($start, 3),
                'end' => round($end, 3),
                'duration' => round(
                    $end - $start,
                    3
                ),
            ];
        }

        $duration = (float) end($ends);

        return [
            'audio' => 'audio/voiceover.mp3',
            'duration' => round($duration, 3),
            'scenes' => $timedScenes,
        ];
    }

    private function saveAudio(
        Project $project,
        string $audio
    ): string {
        $directory = "projects/{$project->id}/audio";

        Storage::disk('local')->makeDirectory(
            $directory
        );

        $path = "{$directory}/voiceover.mp3";

        Storage::disk('local')->put(
            $path,
            $audio
        );

        return $path;
    }

    private function saveTiming(
        Project $project,
        array $timing
    ): string {
        $directory = "projects/{$project->id}/audio";

        Storage::disk('local')->makeDirectory(
            $directory
        );

        $path = "{$directory}/timing.json";

        Storage::disk('local')->put(
            $path,
            json_encode(
                $timing,
                JSON_PRETTY_PRINT |
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES |
                JSON_THROW_ON_ERROR
            )
        );

        return $path;
    }
}