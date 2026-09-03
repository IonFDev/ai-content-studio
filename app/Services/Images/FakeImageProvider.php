<?php
namespace App\Services\Images;
use App\Contracts\Images\ImageProvider;
use App\Models\Scene;
class FakeImageProvider implements ImageProvider {
 public function generateImage(Scene $scene, array $references = [], array $options = []): array {
  $files = glob(resource_path('images/fake/*.png')) ?: [];
  if (!$files) throw new \RuntimeException('Fake image assets are missing.');
  $file = $files[array_rand($files)];
  return ['contents'=>file_get_contents($file),'extension'=>'png','mime'=>'image/png'];
 }
}
