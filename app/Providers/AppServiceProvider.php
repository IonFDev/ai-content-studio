<?php
namespace App\Providers;
use App\Contracts\AI\AIProvider;
use App\Contracts\Images\ImageProvider;
use App\Contracts\Transcription\TranscriptionProvider;
use App\Services\AI\ClaudeProvider;
use App\Services\AI\FakeAIProvider;
use App\Services\Images\FakeImageProvider;
use App\Services\Images\FluxProvider;
use App\Services\Images\IdeogramProvider;
use App\Services\Images\ImagenProvider;
use App\Services\Transcription\FakeTranscriptionProvider;
use App\Services\Transcription\WhisperProvider;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
class AppServiceProvider extends ServiceProvider {
 public function register(): void {
  $this->app->bind(AIProvider::class, function () { return match(config('youtube_studio.providers.ai', 'fake')) { 'fake'=>app(FakeAIProvider::class), 'claude'=>app(ClaudeProvider::class), default=>throw new InvalidArgumentException('Unknown AI provider.') }; });
  $this->app->bind(ImageProvider::class, function () { return match(config('youtube_studio.providers.image', 'fake')) { 'fake'=>app(FakeImageProvider::class), 'ideogram'=>app(IdeogramProvider::class), 'flux'=>app(FluxProvider::class), 'imagen'=>app(ImagenProvider::class), default=>throw new InvalidArgumentException('Unknown image provider.') }; });
  $this->app->bind(TranscriptionProvider::class, function () { return match(config('youtube_studio.providers.transcription', 'fake')) { 'fake'=>app(FakeTranscriptionProvider::class), 'whisper'=>app(WhisperProvider::class), default=>throw new InvalidArgumentException('Unknown transcription provider.') }; });
 }
 public function boot(): void {}
}
