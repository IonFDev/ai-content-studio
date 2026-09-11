<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">

    <style>
        @page {
            margin: 35px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #111;
        }

        h1,
        h2,
        h3,
        p {
            margin-top: 0;
        }

        .cover {
            margin-bottom: 25px;
        }

        .project-title {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 6px;
        }

        .project-meta {
            color: #666;
            font-size: 9px;
        }

        .scene {
            page-break-after: always;
        }

        .scene:last-child {
            page-break-after: auto;
        }

        .scene-header {
            border-bottom: 2px solid #111;
            padding-bottom: 8px;
            margin-bottom: 14px;
        }

        .scene-number {
            font-size: 9px;
            font-weight: bold;
            color: #666;
            text-transform: uppercase;
        }

        .scene-title {
            font-size: 18px;
            font-weight: bold;
            margin-top: 3px;
        }

        .section {
            margin-bottom: 14px;
        }

        .section-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 6px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 4px;
        }

        .narration {
            line-height: 1.5;
        }

        .image-container {
            width: 100%;
            text-align: center;
            margin-bottom: 14px;
        }

        .scene-image {
            max-width: 100%;
            max-height: 330px;
        }

        .no-image {
            border: 1px dashed #aaa;
            padding: 35px;
            text-align: center;
            color: #777;
        }

        .manual-item {
            border: 1px solid #ddd;
            padding: 8px;
            margin-bottom: 7px;
        }

        .manual-type {
            font-size: 8px;
            font-weight: bold;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .manual-description {
            font-weight: bold;
            margin-bottom: 3px;
        }

        .manual-details {
            color: #444;
            line-height: 1.4;
        }

        .position {
            margin-top: 4px;
            color: #666;
            font-size: 8px;
        }

        .notes {
            white-space: pre-line;
            line-height: 1.5;
        }

        .checklist {
            margin: 0;
            padding-left: 18px;
        }

        .checklist li {
            margin-bottom: 4px;
        }

        .prompt {
            font-size: 8px;
            color: #555;
            background: #f5f5f5;
            padding: 8px;
            line-height: 1.4;
        }
    </style>
</head>

<body>

@foreach($scenes as $scene)

    <div class="scene">

        <div class="scene-header">
            <div class="scene-number">
                Escena {{ str_pad($scene->order, 2, '0', STR_PAD_LEFT) }}
            </div>

            <div class="scene-title">
                Hoja de producción
            </div>
        </div>

        <div class="section">
            <div class="section-title">
                Narración
            </div>

            <div class="narration">
                {{ $scene->narration }}
            </div>
        </div>

        <div class="section">
            <div class="section-title">
                Imagen
            </div>

            <div class="image-container">
                @if($scene->image_data_uri)
                    <img
                        src="{{ $scene->image_data_uri }}"
                        class="scene-image"
                    >
                @else
                    <div class="no-image">
                        Imagen todavía no generada
                    </div>
                @endif
            </div>
        </div>

        <div class="section">
            <div class="section-title">
                Elementos que añadir manualmente
            </div>

            @if(!empty($scene->manual_elements))

                @foreach($scene->manual_elements as $element)

                    <div class="manual-item">

                        <div class="manual-type">
                            {{ $element['type'] ?? 'elemento' }}
                        </div>

                        <div class="manual-description">
                            {{ $element['description'] ?? '' }}
                        </div>

                        <div class="manual-details">
                            {{ $element['details'] ?? '' }}
                        </div>

                        @if(!empty($element['position']))
                            <div class="position">
                                Posición: {{ $element['position'] }}
                            </div>
                        @endif

                    </div>

                @endforeach

            @else

                <div class="no-image">
                    No hay elementos manuales indicados.
                </div>

            @endif
        </div>

        @if($scene->animation_notes)

            <div class="section">
                <div class="section-title">
                    Animación
                </div>

                <div class="notes">
                    {{ $scene->animation_notes }}
                </div>
            </div>

        @endif

        @if($scene->production_notes)

            <div class="section">
                <div class="section-title">
                    Notas de producción
                </div>

                <div class="notes">
                    {{ $scene->production_notes }}
                </div>
            </div>

        @endif

        <div class="section">
            <div class="section-title">
                Image Prompt
            </div>

            <div class="prompt">
                {{ $scene->image_prompt }}
            </div>
        </div>

    </div>

@endforeach

</body>
</html>
