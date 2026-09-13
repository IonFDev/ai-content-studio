<?php

namespace App\Contracts\Images;

use App\Models\Scene;

interface ImageProvider
{
    /**
     * Generate the image for a scene.
     *
     * Returns the generated image contents and metadata.
     */
    public function generateImage(
        Scene $scene,
        array $references = [],
        array $options = []
    ): array;
}