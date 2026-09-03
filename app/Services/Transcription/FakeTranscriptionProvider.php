<?php
namespace App\Services\Transcription;
use App\Contracts\Transcription\TranscriptionProvider;
use App\Models\Project;
class FakeTranscriptionProvider implements TranscriptionProvider {
 public function transcribe(Project $project): string {
  return "[FAKE TRANSCRIPTION]\n\nEl precio del oro está recibiendo atención por una combinación de expectativas sobre los tipos de interés, compras de bancos centrales, incertidumbre geopolítica y demanda de activos refugio. Esta transcripción ficticia existe únicamente para probar el flujo de producción sin conectar ninguna API externa.\n\nEl objetivo del nuevo vídeo será explicar el fenómeno desde un enfoque propio, separando los factores estructurales de los movimientos de corto plazo y evitando copiar el contenido del vídeo de referencia.";
 }
}
