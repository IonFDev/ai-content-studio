<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <style>

        @page {
            margin: 28px 34px 32px 34px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;

            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            line-height: 1.45;

            color: #171717;
            background: #ffffff;
        }


        /* =====================================================
           GLOBAL
        ====================================================== */

        h1,
        h2,
        h3,
        p {
            margin: 0;
        }

        .muted {
            color: #8c8c84;
        }

        .accent {
            color: #ff6247;
        }


        /* =====================================================
           SCENE PAGE
        ====================================================== */

        .scene {
            page-break-after: always;
        }

        .scene:last-child {
            page-break-after: auto;
        }


        /* =====================================================
           HEADER
        ====================================================== */

        .scene-header {
            width: 100%;

            border-bottom: 1px solid #deded8;

            padding-bottom: 14px;
            margin-bottom: 18px;
        }

        .scene-header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .scene-header-left {
            width: 70%;
            vertical-align: bottom;
        }

        .scene-header-right {
            width: 30%;
            vertical-align: bottom;

            text-align: right;
        }

        .scene-label {
            margin-bottom: 4px;

            color: #ff6247;

            font-size: 7px;
            font-weight: bold;

            letter-spacing: 1.4px;
            text-transform: uppercase;
        }

        .scene-number {
            font-size: 20px;
            line-height: 1;

            font-weight: bold;
            letter-spacing: -0.5px;
        }

        .scene-title {
            margin-top: 5px;

            color: #666660;

            font-size: 8px;
            font-weight: bold;

            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .scene-meta {
            color: #8c8c84;

            font-size: 7px;
            line-height: 1.5;
        }

        .scene-meta strong {
            color: #55554f;
            font-weight: bold;
        }


        /* =====================================================
           INTRO / SUMMARY
        ====================================================== */

        .scene-summary {
            width: 100%;

            margin-bottom: 18px;

            border: 1px solid #e2e2dc;
            border-radius: 7px;

            background: #fafaf8;
        }

        .scene-summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .scene-summary-cell {
            width: 25%;

            padding: 10px 12px;

            border-right: 1px solid #e2e2dc;
        }

        .scene-summary-cell:last-child {
            border-right: 0;
        }

        .summary-label {
            margin-bottom: 4px;

            color: #aaa9a1;

            font-size: 6.5px;
            font-weight: bold;

            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .summary-value {
            color: #44443f;

            font-size: 8px;
            font-weight: bold;
        }


        /* =====================================================
           SECTIONS
        ====================================================== */

        .section {
            margin-bottom: 17px;
        }

        .section-header {
            margin-bottom: 7px;

            padding-bottom: 5px;

            border-bottom: 1px solid #e1e1db;
        }

        .section-title {
            color: #77776f;

            font-size: 7px;
            font-weight: bold;

            text-transform: uppercase;
            letter-spacing: 0.85px;
        }


        /* =====================================================
           NARRATION
        ====================================================== */

        .narration {
            color: #292925;

            font-size: 10px;
            line-height: 1.6;
        }


        /* =====================================================
           VISUAL CONCEPT
        ====================================================== */

        .visual-concept {
            padding: 10px 12px;

            border-left: 3px solid #ff6247;

            background: #fff7f4;

            color: #5e4f49;

            font-size: 9px;
            line-height: 1.55;
        }


        /* =====================================================
           VISUAL INFORMATION
        ====================================================== */

        .visual-info-table {
            width: 100%;

            border-collapse: collapse;
        }

        .visual-info-cell {
            width: 50%;

            padding: 10px 12px;

            border: 1px solid #e3e3dd;

            vertical-align: top;
        }

        .visual-info-cell + .visual-info-cell {
            border-left: 0;
        }

        .info-label {
            margin-bottom: 5px;

            color: #aaa9a1;

            font-size: 6.5px;
            font-weight: bold;

            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .info-value {
            color: #55554f;

            font-size: 8px;
            line-height: 1.5;
        }


        /* =====================================================
           IMAGE
        ====================================================== */

        .image-container {
            width: 100%;

            padding: 8px;

            border: 1px solid #deded8;
            border-radius: 7px;

            background: #f5f5f2;

            text-align: center;
        }

        .scene-image {
            max-width: 100%;
            max-height: 345px;
        }

        .no-image {
            height: 220px;

            border: 1px dashed #c8c8c0;
            border-radius: 5px;

            background: #fafaf8;

            text-align: center;
            color: #999991;
        }

        .no-image-inner {
            padding-top: 100px;
        }

        .no-image-number {
            margin-bottom: 5px;

            color: #c0c0b8;

            font-size: 14px;
            font-weight: bold;
        }

        .no-image-text {
            font-size: 7px;

            text-transform: uppercase;
            letter-spacing: 0.8px;
        }


        /* =====================================================
           MANUAL ELEMENTS
        ====================================================== */

        .manual-table {
            width: 100%;

            border-collapse: separate;
            border-spacing: 0 6px;
        }

        .manual-item {
            padding: 9px 10px;

            border: 1px solid #e1e1db;
            border-radius: 6px;

            background: #fcfcfa;
        }

        .manual-type {
            margin-bottom: 4px;

            color: #ff6247;

            font-size: 6.5px;
            font-weight: bold;

            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .manual-description {
            margin-bottom: 4px;

            color: #454540;

            font-size: 8px;
            font-weight: bold;
        }

        .manual-details {
            color: #73736c;

            font-size: 7.5px;
            line-height: 1.45;
        }

        .position {
            margin-top: 5px;

            color: #999991;

            font-size: 6.5px;
        }

        .empty-note {
            padding: 12px;

            border: 1px dashed #d2d2cb;
            border-radius: 6px;

            background: #fbfbf8;

            color: #999991;

            font-size: 7px;
        }


        /* =====================================================
           NOTES
        ====================================================== */

        .notes-grid {
            width: 100%;

            border-collapse: collapse;
        }

        .notes-cell {
            width: 50%;

            padding: 10px 12px;

            border: 1px solid #e2e2dc;

            vertical-align: top;
        }

        .notes-cell + .notes-cell {
            border-left: 0;
        }

        .notes {
            white-space: pre-line;

            color: #666660;

            font-size: 7.5px;
            line-height: 1.55;
        }


        /* =====================================================
           CHECKLIST
        ====================================================== */

        .checklist {
            margin: 0;
            padding: 0;

            list-style: none;
        }

        .checklist li {
            position: relative;

            padding-left: 14px;
            margin-bottom: 5px;

            color: #666660;

            font-size: 7px;
        }

        .checklist li:before {
            content: "□";

            position: absolute;
            left: 0;

            color: #999991;

            font-size: 8px;
        }


        /* =====================================================
           PROMPT
        ====================================================== */

        .prompt-box {
            padding: 10px 12px;

            border: 1px solid #dfdfd9;
            border-radius: 6px;

            background: #f8f8f5;
        }

        .prompt-label {
            margin-bottom: 6px;

            color: #aaa9a1;

            font-size: 6.5px;
            font-weight: bold;

            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .prompt {
            color: #666660;

            font-family: DejaVu Sans, sans-serif;

            font-size: 7px;
            line-height: 1.5;
        }


        /* =====================================================
           FOOTER
        ====================================================== */

        .scene-footer {
            margin-top: 18px;
            padding-top: 7px;

            border-top: 1px solid #e3e3dd;

            color: #aaa9a1;

            font-size: 6.5px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

    </style>
</head>


<body>

@foreach($scenes as $scene)

    <div class="scene">


        {{-- =====================================================
             HEADER
        ====================================================== --}}

        <div class="scene-header">

            <table class="scene-header-table">

                <tr>

                    <td class="scene-header-left">

                        <div class="scene-label">
                            YouTube Studio · Production Guide
                        </div>

                        <div class="scene-number">
                            {{ str_pad($scene->order, 2, '0', STR_PAD_LEFT) }}
                        </div>

                        <div class="scene-title">
                            Hoja de producción
                        </div>

                    </td>

                    <td class="scene-header-right">

                        <div class="scene-meta">

                            @if($scene->character_role)
                                <div>
                                    Personaje:
                                    <strong>{{ $scene->character_role }}</strong>
                                </div>
                            @endif

                            @if($scene->shot_type)
                                <div>
                                    Plano:
                                    <strong>{{ $scene->shot_type }}</strong>
                                </div>
                            @endif

                            @if($scene->image_status)
                                <div>
                                    Imagen:
                                    <strong>{{ $scene->image_status }}</strong>
                                </div>
                            @endif

                        </div>

                    </td>

                </tr>

            </table>

        </div>


        {{-- =====================================================
             SUMMARY
        ====================================================== --}}

        <div class="scene-summary">

            <table class="scene-summary-table">

                <tr>

                    <td class="scene-summary-cell">

                        <div class="summary-label">
                            Escena
                        </div>

                        <div class="summary-value">
                            {{ str_pad($scene->order, 2, '0', STR_PAD_LEFT) }}
                        </div>

                    </td>


                    <td class="scene-summary-cell">

                        <div class="summary-label">
                            Personaje
                        </div>

                        <div class="summary-value">
                            {{ $scene->character_role ?: '—' }}
                        </div>

                    </td>


                    <td class="scene-summary-cell">

                        <div class="summary-label">
                            Plano
                        </div>

                        <div class="summary-value">
                            {{ $scene->shot_type ?: '—' }}
                        </div>

                    </td>


                    <td class="scene-summary-cell">

                        <div class="summary-label">
                            Estado imagen
                        </div>

                        <div class="summary-value">
                            {{ $scene->image_status ?: '—' }}
                        </div>

                    </td>

                </tr>

            </table>

        </div>


        {{-- =====================================================
             NARRATION
        ====================================================== --}}

        <div class="section">

            <div class="section-header">
                <div class="section-title">
                    Narración
                </div>
            </div>

            <div class="narration">
                {{ $scene->narration }}
            </div>

        </div>


        {{-- =====================================================
             VISUAL CONCEPT
        ====================================================== --}}

        @if($scene->visual_concept)

            <div class="section">

                <div class="section-header">
                    <div class="section-title">
                        Concepto visual
                    </div>
                </div>

                <div class="visual-concept">
                    {{ $scene->visual_concept }}
                </div>

            </div>

        @endif


        {{-- =====================================================
             VISUAL INFORMATION
        ====================================================== --}}

        @if($scene->visual_description || $scene->visual_metaphor)

            <div class="section">

                <div class="section-header">
                    <div class="section-title">
                        Dirección visual
                    </div>
                </div>

                <table class="visual-info-table">

                    <tr>

                        @if($scene->visual_description)

                            <td class="visual-info-cell">

                                <div class="info-label">
                                    Descripción visual
                                </div>

                                <div class="info-value">
                                    {{ $scene->visual_description }}
                                </div>

                            </td>

                        @endif


                        @if($scene->visual_metaphor)

                            <td class="visual-info-cell">

                                <div class="info-label">
                                    Metáfora visual
                                </div>

                                <div class="info-value">
                                    {{ $scene->visual_metaphor }}
                                </div>

                            </td>

                        @endif

                    </tr>

                </table>

            </div>

        @endif


        {{-- =====================================================
             IMAGE
        ====================================================== --}}

        <div class="section">

            <div class="section-header">
                <div class="section-title">
                    Imagen generada
                </div>
            </div>

            <div class="image-container">

                @if($scene->image_data_uri)

                    <img
                        src="{{ $scene->image_data_uri }}"
                        class="scene-image"
                    >

                @else

                    <div class="no-image">

                        <div class="no-image-inner">

                            <div class="no-image-number">
                                {{ str_pad($scene->order, 2, '0', STR_PAD_LEFT) }}
                            </div>

                            <div class="no-image-text">
                                Imagen todavía no generada
                            </div>

                        </div>

                    </div>

                @endif

            </div>

        </div>


        {{-- =====================================================
             MANUAL ELEMENTS
        ====================================================== --}}

        <div class="section">

            <div class="section-header">
                <div class="section-title">
                    Elementos que añadir manualmente
                </div>
            </div>

            @if(!empty($scene->manual_elements))

                <table class="manual-table">

                    @foreach($scene->manual_elements as $element)

                        <tr>

                            <td class="manual-item">

                                <div class="manual-type">
                                    {{ $element['type'] ?? 'Elemento' }}
                                </div>

                                @if(!empty($element['description']))

                                    <div class="manual-description">
                                        {{ $element['description'] }}
                                    </div>

                                @endif

                                @if(!empty($element['details']))

                                    <div class="manual-details">
                                        {{ $element['details'] }}
                                    </div>

                                @endif

                                @if(!empty($element['position']))

                                    <div class="position">
                                        Posición · {{ $element['position'] }}
                                    </div>

                                @endif

                            </td>

                        </tr>

                    @endforeach

                </table>

            @else

                <div class="empty-note">
                    No hay elementos manuales indicados para esta escena.
                </div>

            @endif

        </div>


        {{-- =====================================================
             NOTES
        ====================================================== --}}

        @if($scene->animation_notes || $scene->production_notes)

            <div class="section">

                <div class="section-header">
                    <div class="section-title">
                        Producción
                    </div>
                </div>

                <table class="notes-grid">

                    <tr>

                        @if($scene->animation_notes)

                            <td class="notes-cell">

                                <div class="info-label">
                                    Animación
                                </div>

                                <div class="notes">
                                    {{ $scene->animation_notes }}
                                </div>

                            </td>

                        @endif


                        @if($scene->production_notes)

                            <td class="notes-cell">

                                <div class="info-label">
                                    Notas de producción
                                </div>

                                <div class="notes">
                                    {{ $scene->production_notes }}
                                </div>

                            </td>

                        @endif

                    </tr>

                </table>

            </div>

        @endif


        {{-- =====================================================
             PROMPT
        ====================================================== --}}

        @if($scene->image_prompt)

            <div class="section">

                <div class="section-header">
                    <div class="section-title">
                        Prompt de imagen
                    </div>
                </div>

                <div class="prompt-box">

                    <div class="prompt-label">
                        FLUX prompt
                    </div>

                    <div class="prompt">
                        {{ $scene->image_prompt }}
                    </div>

                </div>

            </div>

        @endif


        {{-- =====================================================
             FOOTER
        ====================================================== --}}

        <div class="scene-footer">
            Production guide · Scene {{ str_pad($scene->order, 2, '0', STR_PAD_LEFT) }}
        </div>

    </div>

@endforeach

</body>
</html>