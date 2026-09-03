import os
import re
import subprocess
import tempfile
from pathlib import Path
from threading import Lock
import traceback

from fastapi import FastAPI, HTTPException
from pydantic import BaseModel, Field
from faster_whisper import WhisperModel
from pydantic import BaseModel, Field, field_validator

APP_ROOT = Path("/data/app")
MODEL_NAME = os.getenv("WHISPER_MODEL", "small")
DEVICE = os.getenv("WHISPER_DEVICE", "cpu")
COMPUTE_TYPE = os.getenv("WHISPER_COMPUTE_TYPE", "int8")

app = FastAPI(title="YouTube Studio Whisper Service", version="1.0.0")
_model = None
_model_lock = Lock()
_transcription_lock = Lock()


class TranscriptionRequest(BaseModel):
    project_id: int = Field(ge=1)
    source_url: str = Field(min_length=1, max_length=2048)
    language: str | None = Field(default=None, min_length=2, max_length=5)

    @field_validator("language", mode="before")
    @classmethod
    def normalize_language(cls, value):
        if value is None:
            return None

        if isinstance(value, str):
            value = value.strip()

            if value == "":
                return None

        return value


def get_model() -> WhisperModel:
    global _model
    if _model is None:
        with _model_lock:
            if _model is None:
                _model = WhisperModel(
                    MODEL_NAME,
                    device=DEVICE,
                    compute_type=COMPUTE_TYPE,
                    download_root="/models",
                )
    return _model


def validate_youtube_url(url: str) -> None:
    allowed = (
        "youtube.com/watch?",
        "www.youtube.com/watch?",
        "youtu.be/",
        "youtube.com/shorts/",
        "www.youtube.com/shorts/",
    )
    if not any(url.startswith(f"https://{prefix}") or url.startswith(f"http://{prefix}") for prefix in allowed):
        raise HTTPException(status_code=422, detail="La URL debe ser una URL válida de YouTube.")


def safe_project_dir(project_id: int) -> Path:
    path = APP_ROOT / "projects" / str(project_id)
    path.mkdir(parents=True, exist_ok=True)
    return path


def download_audio(source_url: str, output_dir: Path) -> Path:
    source_dir = output_dir / "source"
    source_dir.mkdir(parents=True, exist_ok=True)
    output_template = str(source_dir / "source.%(ext)s")

    command = [
        "yt-dlp",
        "--no-playlist",
        "--restrict-filenames",
        "--extract-audio",
        "--audio-format", "wav",
        "--audio-quality", "0",
        "--output", output_template,
        source_url,
    ]

    result = subprocess.run(
        command,
        capture_output=True,
        text=True,
        timeout=1800,
    )

    if result.returncode != 0:
        detail = (result.stderr or result.stdout or "Error desconocido de yt-dlp").strip()
        raise RuntimeError(detail[-4000:])

    candidates = sorted(source_dir.glob("source.*"))
    if not candidates:
        raise RuntimeError("yt-dlp terminó correctamente, pero no se encontró el archivo de audio.")

    return candidates[0]


def transcribe_audio(
    audio_path: Path,
    language: str | None
) -> tuple[str, str | None, float | None]:

    model = get_model()

    segments, info = model.transcribe(
        str(audio_path),
        language="es",
        beam_size=5,
        vad_filter=False,
        condition_on_previous_text=False,
        temperature=0,
    )

    parts: list[str] = []
    segment_count = 0

    for segment in segments:
        segment_count += 1

        text = segment.text.strip()

        if text:
            parts.append(text)

    text = "\n\n".join(parts)

    if not text:
        raise RuntimeError(
            "Whisper no ha generado texto. "
            f"segmentos={segment_count}, "
            f"idioma={info.language}, "
            f"probabilidad_idioma={info.language_probability}, "
            f"duracion={info.duration}"
        )

    return text, info.language, info.duration


@app.get("/health")
def health() -> dict:
    return {
        "status": "ok",
        "model": MODEL_NAME,
        "device": DEVICE,
        "compute_type": COMPUTE_TYPE,
        "model_loaded": _model is not None,
    }


@app.post("/transcribe")
def transcribe(request: TranscriptionRequest) -> dict:
    validate_youtube_url(request.source_url)
    project_dir = safe_project_dir(request.project_id)

    with _transcription_lock:
        try:
            audio_path = download_audio(request.source_url, project_dir)
            text, detected_language, duration = transcribe_audio(audio_path, request.language)

            if not text.strip():
                raise RuntimeError("Whisper no ha devuelto texto para este audio.")

            transcript_dir = project_dir / "transcript"
            transcript_dir.mkdir(parents=True, exist_ok=True)
            transcript_path = transcript_dir / "transcript.txt"
            transcript_path.write_text(text, encoding="utf-8")

            return {
                "success": True,
                "text": text,
                "language": detected_language,
                "duration": duration,
                "audio_path": str(audio_path.relative_to(APP_ROOT)),
                "transcript_path": str(transcript_path.relative_to(APP_ROOT)),
            }
        except subprocess.TimeoutExpired:
            raise HTTPException(status_code=504, detail="La descarga de YouTube ha superado el tiempo máximo permitido.")
        except HTTPException:
            raise
        except Exception as exc:
            traceback.print_exc()
            raise HTTPException(
                status_code=500,
                detail=f"{type(exc).__name__}: {exc}"
            )
