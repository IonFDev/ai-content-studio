<?php
namespace App\Services\Images;
use App\Contracts\Images\ImageProvider;
use App\Models\Scene;
use RuntimeException;
class ImagenProvider implements ImageProvider { public function generateImage(Scene $scene, array $references = [], array $options = []): array { throw new RuntimeException('ImagenProvider is not enabled in the MVP.'); } }
