@extends('layouts.app')

@section('title', 'Editar personaje · YouTube Studio')
@section('section', 'Editar personaje')

@section('breadcrumbs')
    <a
        href="{{ route('characters.index') }}"
        class="ui-breadcrumb-link"
    >
        Personajes
    </a>

    <span class="ui-breadcrumb-separator">/</span>

    <span>{{ $character->name }}</span>
@endsection

@section('content')

    <div class="character-edit-page">

        {{-- HEADER --}}
        <header class="character-edit-header">

            <div>

                <span class="page-eyebrow">
                    Character library
                </span>

                <div class="character-edit-title-row">

                    <h1 class="page-title">
                        {{ $character->name }}
                    </h1>

                    <span class="character-edit-state">
                        Personaje
                    </span>

                </div>

                <p class="page-description">
                    Edita la identidad del personaje y actualiza sus referencias maestras.
                </p>

            </div>


            {{-- DELETE --}}
            <form
                method="POST"
                action="{{ route('characters.destroy', $character) }}"
                onsubmit="return confirm('¿Eliminar este personaje?')"
            >

                @csrf
                @method('DELETE')

                <button
                    type="submit"
                    class="ui-btn ui-btn-danger-outline"
                >
                    Eliminar personaje
                </button>

            </form>

        </header>


        <div class="character-edit-layout">

            {{-- MAIN --}}
            <section class="ui-panel character-edit-panel">

                <div class="ui-panel-header">

                    <div>

                        <span class="panel-eyebrow">
                            Character setup
                        </span>

                        <h2 class="panel-title">
                            Información del personaje
                        </h2>

                        <p class="panel-description">
                            Los cambios se aplicarán a las futuras generaciones que utilicen este personaje.
                        </p>

                    </div>

                </div>


                <form
                    method="POST"
                    enctype="multipart/form-data"
                    action="{{ route('characters.update', $character) }}"
                    class="ui-form character-edit-form"
                >

                    @csrf
                    @method('PUT')


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
                            value="{{ $character->name }}"
                            required
                            autocomplete="off"
                        >

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
                        >{{ $character->description }}</textarea>

                    </div>


                    {{-- REFERENCES --}}
                    <div class="character-edit-references">

                        <div class="character-edit-references-heading">

                            <div>

                                <span class="panel-eyebrow">
                                    Master references
                                </span>

                                <h3>
                                    Referencias visuales
                                </h3>

                                <p>
                                    Sustituye una referencia únicamente cuando quieras cambiar
                                    la identidad visual maestra del personaje.
                                </p>

                            </div>

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
                                            Identidad, proporciones y diseño general.
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
                                        class="reference-preview {{ $character->reference_sheet_path ? 'has-image' : '' }}"
                                    >

                                        @if($character->reference_sheet_path)

                                            <img
                                                src="{{ route('characters.image', [$character, 'sheet']) }}"
                                                class="reference-preview-image"
                                                alt="Character Reference Sheet"
                                            >

                                            <div class="reference-preview-overlay">
                                                Reemplazar referencia
                                            </div>

                                        @else

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

                                        @endif

                                    </div>

                                </label>


                                <div class="reference-footer">

                                    @if($character->reference_sheet_path)
                                        Referencia actual · PNG · JPG · WEBP · máximo 10 MB
                                    @else
                                        No hay referencia · PNG · JPG · WEBP · máximo 10 MB
                                    @endif

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
                                            Detalle visual y apariencia del personaje.
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
                                        class="reference-preview {{ $character->portrait_reference_path ? 'has-image' : '' }}"
                                    >

                                        @if($character->portrait_reference_path)

                                            <img
                                                src="{{ route('characters.image', [$character, 'portrait']) }}"
                                                class="reference-preview-image"
                                                alt="Character Portrait Reference"
                                            >

                                            <div class="reference-preview-overlay">
                                                Reemplazar referencia
                                            </div>

                                        @else

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

                                        @endif

                                    </div>

                                </label>


                                <div class="reference-footer">

                                    @if($character->portrait_reference_path)
                                        Referencia actual · PNG · JPG · WEBP · máximo 10 MB
                                    @else
                                        No hay referencia · PNG · JPG · WEBP · máximo 10 MB
                                    @endif

                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- FOOTER --}}
                    <div class="character-edit-footer">

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
                            Guardar cambios
                        </button>

                    </div>

                </form>

            </section>


            {{-- SIDEBAR --}}
            <aside class="character-edit-sidebar">

                <section class="ui-panel character-status-panel">

                    <div class="character-status-mark">
                        {{ strtoupper(substr($character->name, 0, 1)) }}
                    </div>

                    <div class="character-status-content">

                        <span class="panel-eyebrow">
                            Character
                        </span>

                        <h2>
                            {{ $character->name }}
                        </h2>

                        <p>
                            Referencias maestras utilizadas por el sistema de generación visual.
                        </p>

                    </div>


                    <div class="character-reference-status">

                        <div class="character-reference-status-row">

                            <span>
                                Reference Sheet
                            </span>

                            @if($character->reference_sheet_path)

                                <span class="reference-status reference-status-ready">
                                    Disponible
                                </span>

                            @else

                                <span class="reference-status reference-status-missing">
                                    Falta
                                </span>

                            @endif

                        </div>


                        <div class="character-reference-status-row">

                            <span>
                                Portrait
                            </span>

                            @if($character->portrait_reference_path)

                                <span class="reference-status reference-status-ready">
                                    Disponible
                                </span>

                            @else

                                <span class="reference-status reference-status-missing">
                                    Falta
                                </span>

                            @endif

                        </div>

                    </div>

                </section>


                <div class="character-edit-note">

                    <span class="character-edit-note-label">
                        Importante
                    </span>

                    <p>
                        Cambiar estas referencias afecta a las futuras generaciones.
                        Las imágenes ya generadas en proyectos anteriores no se modifican.
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
                                Nueva referencia · ${file.name}
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