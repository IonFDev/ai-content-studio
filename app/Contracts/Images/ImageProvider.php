<?php
namespace App\Contracts\Images;
use App\Models\Scene;
interface ImageProvider { public function generateImage(Scene $scene, array $references = [], array $options = []): array; }
