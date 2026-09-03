# Local Whisper service

Internal Docker service used by YouTube Studio to download YouTube audio with yt-dlp and transcribe it locally with faster-whisper.

Default configuration:

- model: `small`
- device: `cpu`
- compute type: `int8`

The model is downloaded into the persistent Docker volume `whisper_models` on first use/startup.
