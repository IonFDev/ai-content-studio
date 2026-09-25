@extends('layouts.app')

@section('title', 'Nuevo personaje · YouTube Studio')
@section('section', 'Nuevo personaje')

@section('breadcrumbs')
    <a
        href="{{ route('characters.index') }}"
        class="ui-breadcrumb-link"
    >
        Personajes
    </a>

    <span class="ui-breadcrumb-separator">/</span>

    <span>Nuevo personaje</span>
@endsection

@section('content')

    <div class="character-create-page">

        {{-- HEADER --}}
        <header class="character-create-header">

            <div>

                <span class="page-eyebrow">
                    Character library
                </span>

                <h1 class="page-title">
                    Nuevo personaje
                </h1>

                <p class="page-description">
                    Crea un personaje y define las referencias visuales maestras que utilizará el pipeline.
                </p>

            </div>

        </header>


        <div class="character-create-layout">

            {{-- MAIN --}}
            <section class="ui-panel character-create-panel">

                <div class="ui-panel-header">

                    <div>

                        <span class="panel-eyebrow">
                            Character setup
                        </span>

                        <h2 class="panel-title">
                            Identidad del personaje
                        </h2>

                        <p class="panel-description">
                            Estos datos identifican al personaje dentro de todos los proyectos.
                        </p>

                    </div>

                </div>


                <form
                    method="POST"
                    enctype="multipart/form-data"
                    action="{{ route('characters.store') }}"
                    class="ui-form character-create-form"
                >

                    @csrf


                    {{-- NAME --}}
                    <div class="form-field">

                        <label
                            for="character-name"
                            class="ui-label"
                        >
                            Nombre
                        </label>

                        <input
                            id="character-name"
                            name="name"
                            class="ui-input ui-input-large"
                            value="{{ old('name') }}"
                            required
                            placeholder="Detective Stickman"
                            autocomplete="off"
                        >

                        <span class="field-help">
                            Utiliza el nombre con el que identificarás el personaje en los proyectos.
                        </span>

                    </div>


                    {{-- DESCRIPTION --}}
                    <div class="form-field">

                        <label
                            for="character-description"
                            class="ui-label"
                        >
                            Descripción
                        </label>

                        <textarea
                            id="character-description"
                            name="description"
                            class="ui-textarea character-description-input"
                            rows="5"
                            placeholder="Descripción de la identidad visual, personalidad o uso del personaje."
                        >{{ old('description') }}</textarea>

                        <span class="field-help">
                            Información interna para identificar el personaje y su función visual.
                        </span>

                    </div>


                    {{-- REFERENCES --}}
                    <div class="character-reference-section">

                        <div class="character-reference-heading">

                            <div>

                                <span class="panel-eyebrow">
                                    Master references
                                </span>

                                <h3>
                                    Referencias visuales
                                </h3>

                            </div>

                            <span class="reference-required">
                                2 referencias
                            </span>

                        </div>


                        <div class="character-reference-grid">


                            {{-- REFERENCE SHEET --}}
                            <div class="reference-upload">

                                <div class="reference-upload-header">

                                    <div class="reference-number">
                                        01
                                    </div>

                                    <div>

                                        <div class="reference-title">
                                            Character Reference Sheet
                                        </div>

                                        <div class="reference-description">
                                            Hoja principal de identidad y proporciones.
                                        </div>

                                    </div>

                                </div>


                                <label
                                    for="reference-sheet"
                                    class="reference-dropzone"
                                >

                                    <input
                                        id="reference-sheet"
                                        type="file"
                                        name="reference_sheet"
                                        accept="image/png,image/jpeg,image/webp"
                                        class="reference-file-input"
                                        data-preview-target="reference-sheet-preview"
                                    >

                                    <div
                                        id="reference-sheet-preview"
                                        class="reference-preview"
                                    >

                                        <div class="reference-placeholder">

                                            <div class="reference-upload-icon">
                                                ↑
                                            </div>

                                            <strong>
                                                Seleccionar imagen
                                            </strong>

                                            <span>
                                                Arrastra o selecciona un archivo
                                            </span>

                                        </div>

                                    </div>

                                </label>

                                <div class="reference-footer">
                                    PNG · JPG · WEBP · máximo 10 MB
                                </div>

                            </div>


                            {{-- PORTRAIT --}}
                            <div class="reference-upload">

                                <div class="reference-upload-header">

                                    <div class="reference-number">
                                        02
                                    </div>

                                    <div>

                                        <div class="reference-title">
                                            Character Portrait Reference
                                        </div>

                                        <div class="reference-description">
                                            Referencia detallada del aspecto del personaje.
                                        </div>

                                    </div>

                                </div>


                                <label
                                    for="portrait-reference"
                                    class="reference-dropzone"
                                >

                                    <input
                                        id="portrait-reference"
                                        type="file"
                                        name="portrait_reference"
                                        accept="image/png,image/jpeg,image/webp"
                                        class="reference-file-input"
                                        data-preview-target="portrait-reference-preview"
                                    >

                                    <div
                                        id="portrait-reference-preview"
                                        class="reference-preview"
                                    >

                                        <div class="reference-placeholder">

                                            <div class="reference-upload-icon">
                                                ↑
                                            </div>

                                            <strong>
                                                Seleccionar imagen
                                            </strong>

                                            <span>
                                                Arrastra o selecciona un archivo
                                            </span>

                                        </div>

                                    </div>

                                </label>

                                <div class="reference-footer">
                                    PNG · JPG · WEBP · máximo 10 MB
                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- FOOTER --}}
                    <div class="character-create-footer">

                        <a
                            href="{{ route('characters.index') }}"
                            class="ui-btn ui-btn-secondary"
                        >
                            Cancelar
                        </a>

                        <button
                            type="submit"
                            class="ui-btn ui-btn-primary"
                        >
                            Crear personaje
                            <span class="ui-btn-arrow">→</span>
                        </button>

                    </div>

                </form>

            </section>


            {{-- SIDE --}}
            <aside class="character-create-sidebar">

                <section class="ui-panel character-guideline-panel">

                    <div class="character-guideline-icon">
                        ↗
                    </div>

                    <div class="character-guideline-content">

                        <span class="panel-eyebrow">
                            Reference system
                        </span>

                        <h2>
                            Una identidad visual consistente.
                        </h2>

                        <p>
                            Las referencias maestras se utilizan para conservar la identidad
                            del personaje entre las distintas generaciones de imágenes.
                        </p>

                    </div>


                    <div class="character-guideline-list">

                        <div class="character-guideline-item">

                            <span class="guideline-index">
                                01
                            </span>

                            <span>
                                Hoja de referencia
                            </span>

                        </div>

                        <div class="character-guideline-item">

                            <span class="guideline-index">
                                02
                            </span>

                            <span>
                                Retrato
                            </span>

                        </div>

                        <div class="character-guideline-item">

                            <span class="guideline-index">
                                03
                            </span>

                            <span>
                                Generación visual
                            </span>

                        </div>

                    </div>

                </section>


                <div class="character-create-note">

                    <span class="character-create-note-label">
                        Recomendación
                    </span>

                    <p>
                        Utiliza imágenes limpias y suficientemente grandes. La hoja de referencia
                        debe mostrar claramente las proporciones y rasgos que no quieres que cambien.
                    </p>

                </div>

            </aside>

        </div>

    </div>


    {{-- IMAGE PREVIEW --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            document.querySelectorAll('.reference-file-input').forEach(function (input) {

                input.addEventListener('change', function () {

                    const targetId = input.dataset.previewTarget;
                    const target = document.getElementById(targetId);

                    if (!target || !input.files || !input.files[0]) {
                        return;
                    }

                    const file = input.files[0];

                    if (!file.type.startsWith('image/')) {
                        return;
                    }

                    const reader = new FileReader();

                    reader.onload = function (event) {

                        target.innerHTML = `
                            <img
                                src="${event.target.result}"
                                class="reference-preview-image"
                                alt="Vista previa"
                            >
                            <div class="reference-preview-overlay">
                                <span>${file.name}</span>
                            </div>
                        `;

                        target.classList.add('has-image');
                    };

                    reader.readAsDataURL(file);
                });

            });

        });
    </script>

@endsection