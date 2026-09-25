@extends('layouts.app')

@section('title', $project->name . ' · YouTube Studio')
@section('section', 'Proyecto')

@section('breadcrumbs')
    <a href="{{ route('projects.index') }}" class="ui-breadcrumb-link">
        Proyectos
    </a>

    <span class="ui-breadcrumb-separator">/</span>

    <span>{{ $project->name }}</span>
@endsection

@section('content')

    @php($tab = request('tab', 'summary'))

    <div class="project-page">

        {{-- =====================================================
            PROJECT HEADER
        ====================================================== --}}

        <header class="project-header">

            <div class="project-heading">

                <div class="project-heading-top">

                    <span class="project-eyebrow">
                        Proyecto
                    </span>

                    <x-status-badge :status="$project->status" />

                </div>

                <h1 class="project-title">
                    {{ $project->name }}
                </h1>

                <div class="project-meta">

                    <span>
                        {{ $project->character?->name ?? 'Sin personaje' }}
                    </span>

                    <span class="project-meta-dot"></span>

                    <span>
                        Actualizado {{ $project->updated_at->format('d/m/Y H:i') }}
                    </span>

                </div>

            </div>

            <div class="project-header-actions">

                <a
                    href="{{ route('projects.index') }}"
                    class="ui-btn ui-btn-secondary"
                >
                    <svg viewBox="0 0 16 16" aria-hidden="true">
                        <path d="M9.75 3.25 5 8l4.75 4.75L8.25 14.25 2 8l6.25-6.25 1.5 1.5Z"/>
                    </svg>

                    <span>Proyectos</span>
                </a>

            </div>

        </header>


        {{-- =====================================================
            PROJECT NAVIGATION
        ====================================================== --}}

        <nav class="project-tabs">

            <a
                href="{{ route('projects.show', [$project, 'tab' => 'summary']) }}"
                class="project-tab {{ $tab === 'summary' ? 'active' : '' }}"
            >
                <span>Resumen</span>
            </a>

            <a
                href="{{ route('projects.show', [$project, 'tab' => 'source']) }}"
                class="project-tab {{ $tab === 'source' ? 'active' : '' }}"
            >
                <span>Fuente</span>
            </a>

            <a
                href="{{ route('projects.show', [$project, 'tab' => 'script']) }}"
                class="project-tab {{ $tab === 'script' ? 'active' : '' }}"
            >
                <span>Guion</span>
            </a>

            <a
                href="{{ route('projects.show', [$project, 'tab' => 'storyboard']) }}"
                class="project-tab {{ $tab === 'storyboard' ? 'active' : '' }}"
            >
                <span>Storyboard</span>

                @if($scenes->count())
                    <span class="project-tab-count">
                        {{ $scenes->count() }}
                    </span>
                @endif
            </a>

            <a
                href="{{ route('projects.show', [$project, 'tab' => 'youtube']) }}"
                class="project-tab {{ $tab === 'youtube' ? 'active' : '' }}"
            >
                <span>YouTube</span>
            </a>

            <a
                href="{{ route('projects.show', [$project, 'tab' => 'images']) }}"
                class="project-tab {{ $tab === 'images' ? 'active' : '' }}"
            >
                <span>Imágenes</span>

                @if($project->generatedImageCount())
                    <span class="project-tab-count">
                        {{ $project->generatedImageCount() }}
                    </span>
                @endif
            </a>

        </nav>


        {{-- =====================================================
            SUMMARY
        ====================================================== --}}

        @if($tab === 'summary')

            <div class="project-layout">

                <div class="project-main-column">

                    {{-- Overview --}}
                    <section class="ui-panel project-overview-panel">

                        <div class="ui-panel-header">

                            <div>
                                <span class="panel-eyebrow">
                                    Overview
                                </span>

                                <h2 class="panel-title">
                                    Estado del proyecto
                                </h2>
                            </div>

                        </div>

                        <div class="project-stat-grid">

                            <div class="project-stat">

                                <span class="project-stat-label">
                                    Escenas
                                </span>

                                <strong class="project-stat-value">
                                    {{ $project->scenes_count }}
                                </strong>

                            </div>

                            <div class="project-stat">

                                <span class="project-stat-label">
                                    Imágenes
                                </span>

                                <strong class="project-stat-value">
                                    {{ $project->generatedImageCount() }}
                                    <span class="project-stat-total">
                                        / {{ $project->scenes_count }}
                                    </span>
                                </strong>

                            </div>

                            <div class="project-stat">

                                <span class="project-stat-label">
                                    Personaje
                                </span>

                                <strong class="project-stat-value project-stat-value-small">
                                    {{ $project->character?->name ?? '—' }}
                                </strong>

                            </div>

                            <div class="project-stat">

                                <span class="project-stat-label">
                                    Estado
                                </span>

                                <div class="project-stat-status">
                                    <x-status-badge :status="$project->status" />
                                </div>

                            </div>

                        </div>

                    </section>


                    {{-- Source --}}
                    <section class="ui-panel">

                        <div class="ui-panel-header">

                            <div>

                                <span class="panel-eyebrow">
                                    Source
                                </span>

                                <h2 class="panel-title">
                                    Vídeo de referencia
                                </h2>

                            </div>

                            @if($project->source_url)

                                <a
                                    href="{{ $project->source_url }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="panel-header-link"
                                >
                                    Abrir fuente
                                </a>

                            @endif

                        </div>

                        <div class="source-preview">

                            <div class="source-icon">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M8 5.25a1 1 0 0 1 1.53-.85l8.5 5.75a1 1 0 0 1 0 1.7l-8.5 5.75A1 1 0 0 1 8 16.75v-11.5Z"/>
                                </svg>
                            </div>

                            <div class="source-preview-content">

                                <strong>
                                    {{ $project->source_title ?: 'Sin título de fuente' }}
                                </strong>

                                <span>
                                    {{ $project->source_url ?: 'Sin URL de fuente' }}
                                </span>

                            </div>

                        </div>

                    </section>


                    {{-- Project data --}}
                    <section class="ui-panel">

                        <div class="ui-panel-header">

                            <div>
                                <span class="panel-eyebrow">
                                    Configuration
                                </span>

                                <h2 class="panel-title">
                                    Datos del proyecto
                                </h2>
                            </div>

                        </div>

                        <form
                            method="POST"
                            action="{{ route('projects.update', $project) }}"
                            class="ui-form"
                        >

                            @csrf
                            @method('PUT')

                            <div class="form-grid">

                                <div class="form-field form-field-full">

                                    <label
                                        for="project-name"
                                        class="ui-label"
                                    >
                                        Nombre
                                    </label>

                                    <input
                                        id="project-name"
                                        class="ui-input"
                                        name="name"
                                        value="{{ $project->name }}"
                                        autocomplete="off"
                                    >

                                </div>

                                <div class="form-field">

                                    <label
                                        for="project-source-url"
                                        class="ui-label"
                                    >
                                        URL fuente
                                    </label>

                                    <input
                                        id="project-source-url"
                                        class="ui-input"
                                        name="source_url"
                                        value="{{ $project->source_url }}"
                                        autocomplete="off"
                                    >

                                </div>

                                <div class="form-field">

                                    <label
                                        for="project-source-title"
                                        class="ui-label"
                                    >
                                        Título fuente
                                    </label>

                                    <input
                                        id="project-source-title"
                                        class="ui-input"
                                        name="source_title"
                                        value="{{ $project->source_title }}"
                                        autocomplete="off"
                                    >

                                </div>

                            </div>

                            <div class="ui-form-footer">

                                <span class="ui-form-help">
                                    Los cambios se aplican al proyecto actual.
                                </span>

                                <button
                                    type="submit"
                                    class="ui-btn ui-btn-primary"
                                >
                                    Guardar cambios
                                </button>

                            </div>

                        </form>

                    </section>

                </div>


                {{-- ACTIONS --}}
                <aside class="project-sidebar">

                    <section class="ui-panel project-actions-panel">

                        <div class="ui-panel-header">

                            <div>
                                <span class="panel-eyebrow">
                                    Pipeline
                                </span>

                                <h2 class="panel-title">
                                    Acciones
                                </h2>
                            </div>

                        </div>

                        <div class="pipeline-list">

                            <form
                                method="POST"
                                action="{{ route('projects.transcribe', $project) }}"
                                class="pipeline-item"
                            >

                                @csrf

                                <button
                                    type="submit"
                                    class="pipeline-button"
                                    {{ blank($project->source_url) ? 'disabled' : '' }}
                                >

                                    <span class="pipeline-index">
                                        01
                                    </span>

                                    <span class="pipeline-copy">

                                        <strong>
                                            Transcribir
                                        </strong>

                                        <small>
                                            Convierte la fuente en texto.
                                        </small>

                                    </span>

                                    <span class="pipeline-arrow">
                                        →
                                    </span>

                                </button>

                            </form>


                            <form
                                method="POST"
                                action="{{ route('projects.generate-content', $project) }}"
                                class="pipeline-item"
                            >

                                @csrf

                                <button
                                    type="submit"
                                    class="pipeline-button"
                                    {{ blank($project->transcript) ? 'disabled' : '' }}
                                >

                                    <span class="pipeline-index">
                                        02
                                    </span>

                                    <span class="pipeline-copy">

                                        <strong>
                                            Generar contenido
                                        </strong>

                                        <small>
                                            Crea título, guion y metadata.
                                        </small>

                                    </span>

                                    <span class="pipeline-arrow">
                                        →
                                    </span>

                                </button>

                            </form>


                            <form
                                method="POST"
                                action="{{ route('projects.generate-storyboard', $project) }}"
                                class="pipeline-item"
                            >

                                @csrf

                                <button
                                    type="submit"
                                    class="pipeline-button"
                                    {{ blank($project->script) ? 'disabled' : '' }}
                                >

                                    <span class="pipeline-index">
                                        03
                                    </span>

                                    <span class="pipeline-copy">

                                        <strong>
                                            Generar storyboard
                                        </strong>

                                        <small>
                                            Divide el vídeo en escenas.
                                        </small>

                                    </span>

                                    <span class="pipeline-arrow">
                                        →
                                    </span>

                                </button>

                            </form>


                            <form
                                method="POST"
                                action="{{ route('projects.generate-images', $project) }}"
                                class="pipeline-item"
                            >

                                @csrf

                                <button
                                    type="submit"
                                    class="pipeline-button"
                                    {{ $project->scenes_count === 0 ? 'disabled' : '' }}
                                >

                                    <span class="pipeline-index">
                                        04
                                    </span>

                                    <span class="pipeline-copy">

                                        <strong>
                                            Generar imágenes
                                        </strong>

                                        <small>
                                            Produce las imágenes del storyboard.
                                        </small>

                                    </span>

                                    <span class="pipeline-arrow">
                                        →
                                    </span>

                                </button>

                            </form>


                            <form
                                method="POST"
                                action="{{ route('projects.generate-voice', $project) }}"
                                class="pipeline-item"
                            >

                                @csrf

                                <button
                                    type="submit"
                                    class="pipeline-button"
                                    {{ $project->scenes_count === 0 ? 'disabled' : '' }}
                                >

                                    <span class="pipeline-index">
                                        05
                                    </span>

                                    <span class="pipeline-copy">

                                        <strong>
                                            Generar voz
                                        </strong>

                                        <small>
                                            Genera el voiceover completo.
                                        </small>

                                    </span>

                                    <span class="pipeline-arrow">
                                        →
                                    </span>

                                </button>

                            </form>

                        </div>


                        <div class="project-secondary-actions">

                            <div class="secondary-action-label">
                                Producción
                            </div>

                            <a
                                href="{{ route('projects.production-guide', $project) }}"
                                target="_blank"
                                class="secondary-action"
                            >
                                <span>Guía de producción</span>
                                <span>↗</span>
                            </a>

                            <form
                                method="POST"
                                action="{{ route('projects.package', $project) }}"
                            >

                                @csrf

                                <button
                                    type="submit"
                                    class="secondary-action secondary-action-button"
                                >
                                    <span>Descargar paquete</span>
                                    <span>↓</span>
                                </button>

                            </form>

                        </div>

                    </section>

                </aside>

            </div>


        {{-- =====================================================
            SOURCE
        ====================================================== --}}

        @elseif($tab === 'source')

            <section class="ui-panel editor-panel">

                <div class="ui-panel-header editor-panel-header">

                    <div>

                        <span class="panel-eyebrow">
                            Source
                        </span>

                        <h2 class="panel-title">
                            Fuente y transcripción
                        </h2>

                        <p class="panel-description">
                            Revisa la fuente y corrige la transcripción antes de generar contenido.
                        </p>

                    </div>

                </div>


                <div class="source-fields">

                    <div class="form-field">

                        <label class="ui-label">
                            URL de YouTube
                        </label>

                        <input
                            class="ui-input"
                            value="{{ $project->source_url }}"
                            readonly
                        >

                    </div>


                    <div class="form-field">

                        <label class="ui-label">
                            Título fuente
                        </label>

                        <input
                            class="ui-input"
                            value="{{ $project->source_title }}"
                            readonly
                        >

                    </div>

                </div>


                <form
                    method="POST"
                    action="{{ route('projects.transcript.save', $project) }}"
                    class="editor-form"
                >

                    @csrf

                    <div class="editor-form-header">

                        <label class="ui-label">
                            Transcripción
                        </label>

                        <span class="editor-hint">
                            Texto utilizado como entrada para la generación de contenido.
                        </span>

                    </div>

                    <textarea
                        class="ui-textarea ui-textarea-large ui-editor"
                        name="transcript"
                        rows="18"
                        required
                    >{{ $project->transcript }}</textarea>

                    <div class="ui-form-footer">

                        <span class="ui-form-help">
                            Puedes editar manualmente cualquier fragmento antes de continuar.
                        </span>

                        <button
                            type="submit"
                            class="ui-btn ui-btn-primary"
                        >
                            Guardar transcripción
                        </button>

                    </div>

                </form>

            </section>


        {{-- =====================================================
            SCRIPT
        ====================================================== --}}

        @elseif($tab === 'script')

            <section class="ui-panel editor-panel">

                <div class="ui-panel-header editor-panel-header">

                    <div>

                        <span class="panel-eyebrow">
                            Content
                        </span>

                        <h2 class="panel-title">
                            Guion
                        </h2>

                        <p class="panel-description">
                            El texto definitivo que alimentará el storyboard y la narración.
                        </p>

                    </div>

                </div>


                <form
                    method="POST"
                    action="{{ route('projects.script.save', $project) }}"
                    class="editor-form"
                >

                    @csrf

                    <div class="editor-form-header">

                        <label class="ui-label">
                            Guion completo
                        </label>

                        <span class="editor-hint">
                            Editable antes de generar las escenas.
                        </span>

                    </div>

                    <textarea
                        class="ui-textarea ui-textarea-script ui-editor"
                        name="script"
                        rows="24"
                        required
                    >{{ $project->script }}</textarea>

                    <div class="ui-form-footer">

                        <span class="ui-form-help">
                            Guarda aquí cualquier ajuste editorial antes del storyboard.
                        </span>

                        <button
                            type="submit"
                            class="ui-btn ui-btn-primary"
                        >
                            Guardar guion
                        </button>

                    </div>

                </form>

            </section>


        {{-- =====================================================
            STORYBOARD
        ====================================================== --}}

        @elseif($tab === 'storyboard')

            <div class="storyboard-page">

                <div class="section-toolbar">

                    <div>

                        <span class="panel-eyebrow">
                            Visual production
                        </span>

                        <h2 class="section-title">
                            Storyboard
                        </h2>

                        <p class="section-description">
                            {{ $scenes->count() }} escenas · Arrastra para cambiar el orden.
                        </p>

                    </div>


                    @if($scenes->count())

                        <div class="section-toolbar-actions">

                            <a
                                href="{{ route('projects.production-guide', $project) }}"
                                target="_blank"
                                class="ui-btn ui-btn-secondary"
                            >
                                Guía de producción
                            </a>

                            <form
                                method="POST"
                                action="{{ route('projects.scenes.reorder', $project) }}"
                                id="scene-reorder-form"
                            >

                                @csrf

                                <div id="scene-orders"></div>

                                <button
                                    type="submit"
                                    class="ui-btn ui-btn-secondary"
                                >
                                    Guardar orden
                                </button>

                            </form>

                            <form
                                method="POST"
                                action="{{ route('projects.generate-images', $project) }}"
                            >

                                @csrf

                                <button
                                    type="submit"
                                    class="ui-btn ui-btn-primary"
                                >
                                    Generar imágenes
                                </button>

                            </form>

                        </div>

                    @endif

                </div>


                <div
                    class="scene-list"
                    id="scene-list"
                >

                    @forelse($scenes as $scene)

                        <article
                            class="scene-card"
                            data-scene-id="{{ $scene->id }}"
                        >

                            <div class="scene-card-top">

                                <div class="scene-drag-handle">
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                </div>

                                <div class="scene-index">

                                    <span class="scene-index-label">
                                        Scene
                                    </span>

                                    <strong>
                                        {{ str_pad($scene->order, 2, '0', STR_PAD_LEFT) }}
                                    </strong>

                                </div>

                                <div class="scene-card-actions">

                                    <x-status-badge
                                        :status="$scene->image_status"
                                    />

                                    <form
                                        method="POST"
                                        action="{{ route('projects.scenes.generate-image', [$project, $scene]) }}"
                                    >

                                        @csrf

                                        <button
                                            type="submit"
                                            class="ui-btn ui-btn-small ui-btn-secondary"
                                        >
                                            {{ $scene->image_path ? 'Regenerar' : 'Generar imagen' }}
                                        </button>

                                    </form>

                                </div>

                            </div>


                            <div class="scene-content-grid">

                                <div class="scene-content-main">

                                    <div class="scene-block">

                                        <div class="scene-block-label">
                                            Narración
                                        </div>

                                        <div class="scene-narration">
                                            {{ $scene->narration }}
                                        </div>

                                    </div>


                                    @if($scene->visual_concept)

                                        <div class="scene-block">

                                            <div class="scene-block-label">
                                                Concepto visual
                                            </div>

                                            <div class="scene-block-copy">
                                                {{ $scene->visual_concept }}
                                            </div>

                                        </div>

                                    @endif


                                    <div class="scene-block">

                                        <div class="scene-block-label">
                                            Descripción visual
                                        </div>

                                        <div class="scene-block-copy">
                                            {{ $scene->visual_description }}
                                        </div>

                                    </div>


                                    @if($scene->visual_metaphor)

                                        <div class="scene-block">

                                            <div class="scene-block-label">
                                                Metáfora visual
                                            </div>

                                            <div class="scene-block-copy scene-block-accent">
                                                {{ $scene->visual_metaphor }}
                                            </div>

                                        </div>

                                    @endif

                                </div>


                                <div class="scene-content-side">

                                    <div class="scene-specs">

                                        @if($scene->character_role)

                                            <div class="scene-spec">
                                                <span>Personaje</span>
                                                <strong>{{ $scene->character_role }}</strong>
                                            </div>

                                        @endif

                                        @if($scene->shot_type)

                                            <div class="scene-spec">
                                                <span>Plano</span>
                                                <strong>{{ $scene->shot_type }}</strong>
                                            </div>

                                        @endif

                                        @if($scene->visual_priority)

                                            <div class="scene-spec">
                                                <span>Prioridad</span>
                                                <strong>
                                                    {{ is_array($scene->visual_priority)
                                                        ? implode(', ', $scene->visual_priority)
                                                        : $scene->visual_priority }}
                                                </strong>
                                            </div>

                                        @endif

                                    </div>

                                </div>

                            </div>


                            @if($scene->image_prompt)

                                <details class="scene-details">

                                    <summary>
                                        Image prompt
                                    </summary>

                                    <div class="scene-prompt">
                                        {{ $scene->image_prompt }}
                                    </div>

                                </details>

                            @endif


                            @if(!empty($scene->manual_elements))

                                <details class="scene-details">

                                    <summary>
                                        Elementos de producción
                                        <span class="scene-details-count">
                                            {{ count($scene->manual_elements) }}
                                        </span>
                                    </summary>

                                    <div class="manual-elements">

                                        @foreach($scene->manual_elements as $element)

                                            <div class="manual-element">

                                                <div class="manual-element-head">

                                                    <span class="manual-element-type">
                                                        {{ $element['type'] ?? 'elemento' }}
                                                    </span>

                                                </div>

                                                @if(!empty($element['description']))

                                                    <div class="manual-element-description">
                                                        {{ $element['description'] }}
                                                    </div>

                                                @endif

                                                @if(!empty($element['details']))

                                                    <div class="manual-element-meta">
                                                        {{ $element['details'] }}
                                                    </div>

                                                @endif

                                                @if(!empty($element['position']))

                                                    <div class="manual-element-meta">
                                                        Posición · {{ $element['position'] }}
                                                    </div>

                                                @endif

                                            </div>

                                        @endforeach

                                    </div>

                                </details>

                            @endif


                            @if($scene->animation_notes || $scene->production_notes)

                                <div class="scene-notes-grid">

                                    @if($scene->animation_notes)

                                        <div class="scene-note">

                                            <div class="scene-block-label">
                                                Animación
                                            </div>

                                            <div class="scene-note-copy">
                                                {!! nl2br(e($scene->animation_notes)) !!}
                                            </div>

                                        </div>

                                    @endif


                                    @if($scene->production_notes)

                                        <div class="scene-note">

                                            <div class="scene-block-label">
                                                Producción
                                            </div>

                                            <div class="scene-note-copy">
                                                {{ $scene->production_notes }}
                                            </div>

                                        </div>

                                    @endif

                                </div>

                            @endif

                        </article>

                    @empty

                        <div class="empty-state">

                            <div class="empty-state-mark">
                                01
                            </div>

                            <h3>
                                Todavía no hay escenas
                            </h3>

                            <p>
                                Genera el storyboard para convertir el guion en una secuencia visual.
                            </p>

                            <a
                                href="{{ route('projects.show', [$project, 'tab' => 'script']) }}"
                                class="ui-btn ui-btn-secondary"
                            >
                                Ir al guion
                            </a>

                        </div>

                    @endforelse

                </div>

            </div>


        {{-- =====================================================
            YOUTUBE
        ====================================================== --}}

        @elseif($tab === 'youtube')

            <section class="ui-panel editor-panel">

                <div class="ui-panel-header editor-panel-header">

                    <div>

                        <span class="panel-eyebrow">
                            Publishing
                        </span>

                        <h2 class="panel-title">
                            YouTube
                        </h2>

                        <p class="panel-description">
                            Metadata final del vídeo y elementos de publicación.
                        </p>

                    </div>

                </div>


                <form
                    method="POST"
                    action="{{ route('projects.youtube.update', $project) }}"
                    class="ui-form"
                >

                    @csrf


                    {{-- TITLE --}}
                    <div class="form-field">

                        <label
                            for="yt-title"
                            class="ui-label"
                        >
                            Título
                        </label>

                        <div class="copy-field">

                            <input
                                id="yt-title"
                                name="youtube_title"
                                class="ui-input"
                                value="{{ $project->youtube_title }}"
                            >

                            <button
                                type="button"
                                class="ui-btn ui-btn-secondary"
                                data-copy-target="#yt-title"
                            >
                                Copiar
                            </button>

                        </div>

                    </div>


                    {{-- DESCRIPTION --}}
                    <div class="form-field">

                        <label
                            for="yt-description"
                            class="ui-label"
                        >
                            Descripción
                        </label>

                        <div class="copy-field copy-field-textarea">

                            <textarea
                                id="yt-description"
                                name="youtube_description"
                                class="ui-textarea"
                            >{{ $project->youtube_description }}</textarea>

                            <button
                                type="button"
                                class="ui-btn ui-btn-secondary"
                                data-copy-target="#yt-description"
                            >
                                Copiar
                            </button>

                        </div>

                    </div>


                    {{-- KEYWORDS --}}
                    <div class="form-field">

                        <label
                            for="yt-keywords"
                            class="ui-label"
                        >
                            Keywords
                        </label>

                        <div class="copy-field copy-field-textarea">

                            <textarea
                                id="yt-keywords"
                                name="youtube_keywords"
                                class="ui-textarea"
                            >{{ implode(', ', $project->youtube_keywords ?? []) }}</textarea>

                            <button
                                type="button"
                                class="ui-btn ui-btn-secondary"
                                data-copy-target="#yt-keywords"
                            >
                                Copiar
                            </button>

                        </div>

                    </div>


                    {{-- HASHTAGS --}}
                    <div class="form-field">

                        <label
                            for="yt-hashtags"
                            class="ui-label"
                        >
                            Hashtags
                        </label>

                        <div class="copy-field copy-field-textarea">

                            <textarea
                                id="yt-hashtags"
                                name="youtube_hashtags"
                                class="ui-textarea"
                            >{{ implode(', ', $project->youtube_hashtags ?? []) }}</textarea>

                            <button
                                type="button"
                                class="ui-btn ui-btn-secondary"
                                data-copy-target="#yt-hashtags"
                            >
                                Copiar
                            </button>

                        </div>

                    </div>


                    <div class="form-divider"></div>


                    {{-- THUMBNAIL --}}
                    <div class="form-section-heading">

                        <div>
                            <span class="panel-eyebrow">
                                Thumbnail
                            </span>

                            <h3>
                                Concepto visual
                            </h3>
                        </div>

                    </div>


                    <div class="form-field">

                        <label
                            for="thumbnail-idea"
                            class="ui-label"
                        >
                            Idea de thumbnail
                        </label>

                        <textarea
                            id="thumbnail-idea"
                            name="thumbnail_idea"
                            class="ui-textarea"
                        >{{ $project->thumbnail_idea }}</textarea>

                    </div>


                    <div class="form-field">

                        <label
                            for="thumbnail-text"
                            class="ui-label"
                        >
                            Texto de thumbnail
                        </label>

                        <input
                            id="thumbnail-text"
                            name="thumbnail_text"
                            class="ui-input"
                            value="{{ $project->thumbnail_text }}"
                        >

                    </div>


                    <div class="ui-form-footer">

                        <span class="ui-form-help">
                            Guarda los cambios antes de publicar.
                        </span>

                        <button
                            type="submit"
                            class="ui-btn ui-btn-primary"
                        >
                            Guardar metadata
                        </button>

                    </div>

                </form>

            </section>


        {{-- =====================================================
            IMAGES
        ====================================================== --}}

        @elseif($tab === 'images')

            <div class="images-page">

                <div class="section-toolbar">

                    <div>

                        <span class="panel-eyebrow">
                            Visual assets
                        </span>

                        <h2 class="section-title">
                            Imágenes
                        </h2>

                        <p class="section-description">
                            {{ $project->generatedImageCount() }}
                            de
                            {{ $scenes->count() }}
                            imágenes generadas.
                        </p>

                    </div>


                    @if($scenes->count())

                        <form
                            method="POST"
                            action="{{ route('projects.generate-images', $project) }}"
                        >

                            @csrf

                            <button
                                type="submit"
                                class="ui-btn ui-btn-primary"
                            >
                                Generar todas
                            </button>

                        </form>

                    @endif

                </div>


                @if($scenes->count())

                    <div class="image-gallery">

                        @foreach($scenes as $scene)

                            <article class="image-card">

                                <div class="image-card-media">

                                    @if($scene->image_path)

                                        <img
                                            src="{{ route('projects.scenes.image', [$project, $scene]) }}"
                                            alt="Escena {{ $scene->order }}"
                                            class="gallery-image"
                                        >

                                        <div class="image-card-overlay">

                                            <a
                                                href="{{ route('projects.scenes.image', [$project, $scene]) }}"
                                                target="_blank"
                                                class="image-overlay-action"
                                            >
                                                Ver imagen
                                            </a>

                                        </div>

                                    @else

                                        <div class="image-placeholder">

                                            <div class="image-placeholder-mark">
                                                {{ str_pad($scene->order, 2, '0', STR_PAD_LEFT) }}
                                            </div>

                                            <span>
                                                Sin imagen
                                            </span>

                                        </div>

                                    @endif

                                </div>


                                <div class="image-card-footer">

                                    <div>

                                        <div class="image-card-title">
                                            Scene {{ str_pad($scene->order, 2, '0', STR_PAD_LEFT) }}
                                        </div>

                                        <div class="image-card-meta">
                                            {{ $scene->image_path ? 'Generada' : 'Pendiente' }}
                                        </div>

                                    </div>

                                    <div class="image-card-status">
                                        <x-status-badge
                                            :status="$scene->image_status"
                                        />
                                    </div>

                                </div>


                                <div class="image-card-actions">

                                    @if($scene->image_path)

                                        <a
                                            href="{{ route('projects.scenes.image', [$project, $scene]) }}"
                                            target="_blank"
                                            class="ui-btn ui-btn-small ui-btn-secondary"
                                        >
                                            Ver
                                        </a>

                                    @endif

                                    <form
                                        method="POST"
                                        action="{{ route('projects.scenes.generate-image', [$project, $scene]) }}"
                                    >

                                        @csrf

                                        <button
                                            type="submit"
                                            class="ui-btn ui-btn-small ui-btn-secondary"
                                        >
                                            Regenerar
                                        </button>

                                    </form>

                                </div>

                            </article>

                        @endforeach

                    </div>

                @else

                    <div class="empty-state">

                        <div class="empty-state-mark">
                            IMG
                        </div>

                        <h3>
                            No hay escenas todavía
                        </h3>

                        <p>
                            Genera primero el storyboard para crear los assets visuales.
                        </p>

                        <a
                            href="{{ route('projects.show', [$project, 'tab' => 'storyboard']) }}"
                            class="ui-btn ui-btn-secondary"
                        >
                            Ir al storyboard
                        </a>

                    </div>

                @endif

            </div>

        @endif

    </div>

@endsection