@extends('layouts.app')

@section('title', 'Nuevo proyecto · YouTube Studio')
@section('section', 'Nuevo proyecto')

@section('breadcrumbs')
    <a
        href="{{ route('projects.index') }}"
        class="ui-breadcrumb-link"
    >
        Proyectos
    </a>

    <span class="ui-breadcrumb-separator">/</span>

    <span>Nuevo proyecto</span>
@endsection

@section('content')

    <div class="create-project-page">

        {{-- HEADER --}}
        <div class="create-project-header">

            <div>

                <span class="page-eyebrow">
                    New project
                </span>

                <h1 class="page-title">
                    Nuevo proyecto
                </h1>

                <p class="page-description">
                    Crea el espacio de trabajo para producir un nuevo vídeo.
                </p>

            </div>

        </div>


        {{-- CONTENT --}}
        <div class="create-project-layout">

            {{-- FORM --}}
            <section class="ui-panel create-project-form-panel">

                <div class="ui-panel-header">

                    <div>

                        <span class="panel-eyebrow">
                            Project setup
                        </span>

                        <h2 class="panel-title">
                            Información del proyecto
                        </h2>

                        <p class="panel-description">
                            Estos datos serán la base del proyecto y de todo su pipeline de producción.
                        </p>

                    </div>

                </div>


                <form
                    method="POST"
                    action="{{ route('projects.store') }}"
                    class="ui-form create-project-form"
                >

                    @csrf


                    {{-- NAME --}}
                    <div class="form-field">

                        <label
                            for="project-name"
                            class="ui-label"
                        >
                            Nombre del proyecto
                        </label>

                        <input
                            id="project-name"
                            name="name"
                            value="{{ old('name') }}"
                            class="ui-input ui-input-large"
                            required
                            placeholder="Why Is Gold Rising?"
                            autocomplete="off"
                        >

                        <span class="field-help">
                            Usa un nombre interno reconocible para identificar rápidamente el vídeo.
                        </span>

                    </div>


                    {{-- SOURCE URL --}}
                    <div class="form-field">

                        <label
                            for="project-source-url"
                            class="ui-label"
                        >
                            URL del vídeo de YouTube
                        </label>

                        <input
                            id="project-source-url"
                            name="source_url"
                            value="{{ old('source_url') }}"
                            type="url"
                            class="ui-input"
                            required
                            placeholder="https://www.youtube.com/watch?v=..."
                            autocomplete="off"
                        >

                        <span class="field-help">
                            Esta fuente se utilizará para la transcripción del vídeo.
                        </span>

                    </div>


                    {{-- SOURCE TITLE --}}
                    <div class="form-field">

                        <label
                            for="project-source-title"
                            class="ui-label"
                        >
                            Título del vídeo fuente
                            <span class="label-optional">
                                Opcional
                            </span>
                        </label>

                        <input
                            id="project-source-title"
                            name="source_title"
                            value="{{ old('source_title') }}"
                            class="ui-input"
                            placeholder="Título que aparece en YouTube"
                            autocomplete="off"
                        >

                        <span class="field-help">
                            Puedes dejarlo vacío si no lo necesitas.
                        </span>

                    </div>


                    {{-- CHARACTER --}}
                    <div class="form-field">

                        <label
                            for="project-character"
                            class="ui-label"
                        >
                            Personaje
                        </label>

                        <select
                            id="project-character"
                            name="character_id"
                            class="ui-input ui-select"
                            required
                        >

                            <option value="">
                                Selecciona un personaje...
                            </option>

                            @foreach($characters as $character)

                                <option
                                    value="{{ $character->id }}"
                                    @selected(old('character_id') == $character->id)
                                >
                                    {{ $character->name }}
                                </option>

                            @endforeach

                        </select>

                        <span class="field-help">
                            Determina el personaje disponible para las escenas que lo requieran.
                        </span>

                    </div>


                    {{-- FOOTER --}}
                    <div class="create-project-footer">

                        <a
                            href="{{ route('projects.index') }}"
                            class="ui-btn ui-btn-secondary"
                        >
                            Cancelar
                        </a>

                        <button
                            type="submit"
                            class="ui-btn ui-btn-primary"
                        >
                            Crear proyecto
                            <span class="ui-btn-arrow">→</span>
                        </button>

                    </div>

                </form>

            </section>


            {{-- SIDEBAR --}}
            <aside class="create-project-sidebar">

                <section class="ui-panel create-info-panel">

                    <div class="create-info-mark">
                        01
                    </div>

                    <div class="create-info-content">

                        <span class="panel-eyebrow">
                            Production pipeline
                        </span>

                        <h2 class="create-info-title">
                            Un proyecto, todo el proceso.
                        </h2>

                        <p class="create-info-description">
                            Desde la fuente original hasta el paquete final de producción.
                        </p>

                    </div>


                    <div class="create-pipeline">

                        <div class="create-pipeline-item">
                            <span>01</span>
                            <strong>Fuente</strong>
                        </div>

                        <div class="create-pipeline-line"></div>

                        <div class="create-pipeline-item">
                            <span>02</span>
                            <strong>Transcripción</strong>
                        </div>

                        <div class="create-pipeline-line"></div>

                        <div class="create-pipeline-item">
                            <span>03</span>
                            <strong>Contenido</strong>
                        </div>

                        <div class="create-pipeline-line"></div>

                        <div class="create-pipeline-item">
                            <span>04</span>
                            <strong>Storyboard</strong>
                        </div>

                        <div class="create-pipeline-line"></div>

                        <div class="create-pipeline-item">
                            <span>05</span>
                            <strong>Assets</strong>
                        </div>

                    </div>

                </section>


                <section class="create-tip">

                    <span class="create-tip-label">
                        Consejo
                    </span>

                    <p>
                        El nombre del proyecto es interno. El título que acabes publicando
                        en YouTube se gestiona posteriormente desde la pestaña YouTube.
                    </p>

                </section>

            </aside>

        </div>

    </div>

@endsection