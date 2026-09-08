<?php

namespace App\Contracts\Images;

use App\Models\Project;
use App\Models\Scene;

interface ImageProvider
{
    /**
     * Generate the image for a scene.
     *
     * Returns the relative storage path of the generated image.
     */
    public function generate(Scene $scene, Project $project): string;
}