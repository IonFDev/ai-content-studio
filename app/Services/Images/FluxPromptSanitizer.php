<?php

namespace App\Services\Images;

use RuntimeException;

class FluxPromptSanitizer
{
    /**
     * Términos que pueden introducir ambigüedad innecesaria
     * para el sistema de moderación de generación de imágenes.
     *
     * No se pretende bloquear el contenido del vídeo.
     * Solamente se bloquean formulaciones que no son necesarias
     * para construir una ilustración editorial segura.
     */
    private const SENSITIVE_PATTERNS = [
        // Sexual / desnudez
        '/\b(nude|naked|nudity|sex|sexual|sexy|erotic|porn|pornographic)\b/i',

        // Menores en contextos potencialmente ambiguos
        '/\b(child|children|kid|kids|minor|underage|teen|teenager)\b/i',

        // Violencia gráfica
        '/\b(blood|bloody|gore|corpse|dead body|dismember|mutilat|decapitat)\b/i',

        // Autolesión
        '/\b(suicide|suicidal|self[- ]harm|self[- ]injury|cutting wrists)\b/i',

        // Drogas
        '/\b(cocaine|heroin|methamphetamine|meth|fentanyl|crack cocaine)\b/i',

        // Violencia explícita
        '/\b(stab|stabbing|shoot|shooting|shot dead|kill|killing|murder|murdered|execution)\b/i',

        // Armas explícitas
        '/\b(gun|rifle|pistol|firearm|machine gun|weapon|knife|sword)\b/i',
    ];

    /**
     * Palabras que pueden ser perfectamente legítimas en una narración,
     * pero que conviene transformar a una formulación visual más neutra.
     */
    private const REPLACEMENTS = [
        '/\bdead\b/i' => 'inactive',
        '/\bdeath\b/i' => 'loss',
        '/\bdying\b/i' => 'declining',
        '/\bkill\b/i' => 'eliminate',
        '/\bkilled\b/i' => 'removed',
        '/\battack\b/i' => 'confrontation',
        '/\battacked\b/i' => 'confronted',
        '/\bviolence\b/i' => 'conflict',
        '/\bfight\b/i' => 'confrontation',
        '/\bfighting\b/i' => 'confrontation',
        '/\bwar\b/i' => 'geopolitical conflict',
        '/\bcrash\b/i' => 'sharp decline',
        '/\bfalling\b/i' => 'moving downward',
        '/\bfall\b/i' => 'decline',
    ];

    public function sanitize(string $prompt): string
    {
        $prompt = trim($prompt);

        if ($prompt === '') {
            throw new RuntimeException(
                'El prompt de FLUX está vacío.'
            );
        }

        /*
         * Primero aplicamos transformaciones semánticas
         * que conservan el significado visual pero reducen
         * interpretaciones innecesariamente sensibles.
         */
        foreach (self::REPLACEMENTS as $pattern => $replacement) {
            $prompt = preg_replace(
                $pattern,
                $replacement,
                $prompt
            ) ?? $prompt;
        }

        /*
         * Después comprobamos categorías que no debemos
         * enviar literalmente al generador.
         */
        foreach (self::SENSITIVE_PATTERNS as $pattern) {
            if (preg_match($pattern, $prompt)) {
                throw new RuntimeException(
                    'El prompt visual contiene contenido potencialmente '
                    . 'sensible y no se enviará a FLUX.'
                );
            }
        }

        /*
         * Eliminamos instrucciones que intenten convertir
         * el prompt en una conversación o en texto generado.
         */
        $prompt = preg_replace(
            '/\b(write|say|generate|display|show|include)\s+(?:the\s+)?(?:following\s+)?text\b/i',
            'use a simple visual element',
            $prompt
        ) ?? $prompt;

        /*
         * Evitamos texto largo generado dentro de la ilustración.
         */
        $prompt .= "\n\n"
        . "Clean presentation-ready artwork with minimal embedded typography. "
        . "Production text, labels, statistics and exact numerical information "
        . "are added during video editing.";

        return trim($prompt);
    }
}
