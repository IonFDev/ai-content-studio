@extends('layouts.app')

@section('title', 'Proyectos · YouTube Studio')
@section('section', 'Proyectos')

@section('content')

    <div class="projects-page">

        {{-- HEADER --}}
        <div class="page-header">

            <div>
                <div class="eyebrow">
                    Producción
                </div>

                <h1 class="page-title">
                    Proyectos
                </h1>

                <p class="page-description">
                    Cada proyecto contiene la producción completa de un vídeo.
                </p>
            </div>

            <div class="page-header-actions">
                <a
                    href="{{ route('projects.create') }}"
                    class="ui-btn ui-btn-primary"
                >
                    <span class="ui-btn-icon">+</span>
                    Nuevo proyecto
                </a>
            </div>

        </div>


        {{-- PROJECT LIST --}}
        <section class="projects-panel">

            <div class="projects-panel-header">

                <div class="projects-panel-title">
                    <span>Todos los proyectos</span>

                    <span class="projects-count">
                        {{ $projects->total() }}
                    </span>
                </div>

            </div>


            @if($projects->count())

                <div class="projects-table-wrap">

                    <table class="projects-table">

                        <thead>
                            <tr>
                                <th class="project-col-main">
                                    Proyecto
                                </th>

                                <th>
                                    Fuente
                                </th>

                                <th>
                                    Estado
                                </th>

                                <th class="project-col-center">
                                    Escenas
                                </th>

                                <th class="project-col-center">
                                    Imágenes
                                </th>

                                <th>
                                    Actualizado
                                </th>

                                <th class="project-col-action">
                                </th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach($projects as $project)

                                <tr class="project-row">

                                    {{-- PROJECT --}}
                                    <td>

                                        <a
                                            href="{{ route('projects.show', $project) }}"
                                            class="project-name-link"
                                        >
                                            <span class="project-name">
                                                {{ $project->name }}
                                            </span>

                                            @if($project->character?->name)
                                                <span class="project-character">
                                                    {{ $project->character->name }}
                                                </span>
                                            @endif
                                        </a>

                                    </td>


                                    {{-- SOURCE --}}
                                    <td>

                                        <div class="project-source">

                                            <span
                                                class="project-source-title"
                                                title="{{ $project->source_title ?: $project->source_url }}"
                                            >
                                                {{ $project->source_title ?: $project->source_url }}
                                            </span>

                                            @if($project->source_title && $project->source_url)
                                                <span class="project-source-url">
                                                    {{ parse_url($project->source_url, PHP_URL_HOST) }}
                                                </span>
                                            @endif

                                        </div>

                                    </td>


                                    {{-- STATUS --}}
                                    <td>
                                        <x-status-badge
                                            :status="$project->status"
                                        />
                                    </td>


                                    {{-- SCENES --}}
                                    <td class="project-metric">
                                        {{ $project->scenes_count }}
                                    </td>


                                    {{-- IMAGES --}}
                                    <td class="project-metric">

                                        @php
                                            $generatedImages = $project->generatedImageCount();
                                            $sceneCount = max((int) $project->scenes_count, 1);
                                            $imageProgress = min(
                                                100,
                                                round(($generatedImages / $sceneCount) * 100)
                                            );
                                        @endphp

                                        <div class="project-images">

                                            <span class="project-metric-value">
                                                {{ $generatedImages }}
                                            </span>

                                            <span class="project-progress">
                                                <span
                                                    class="project-progress-fill"
                                                    style="width: {{ $imageProgress }}%"
                                                ></span>
                                            </span>

                                        </div>

                                    </td>


                                    {{-- UPDATED --}}
                                    <td>

                                        <div class="project-updated">

                                            <span class="project-updated-date">
                                                {{ $project->updated_at->format('d/m/Y') }}
                                            </span>

                                            <span class="project-updated-time">
                                                {{ $project->updated_at->format('H:i') }}
                                            </span>

                                        </div>

                                    </td>


                                    {{-- ACTION --}}
                                    <td class="project-action">

                                        <a
                                            href="{{ route('projects.show', $project) }}"
                                            class="project-open"
                                            aria-label="Abrir {{ $project->name }}"
                                        >
                                            <span>Abrir</span>

                                            <svg
                                                viewBox="0 0 16 16"
                                                aria-hidden="true"
                                            >
                                                <path d="M5 2.75 10.25 8 5 13.25l1.5 1.5L13.25 8 6.5 1.25 5 2.75Z"/>
                                            </svg>
                                        </a>

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            @else

                {{-- EMPTY STATE --}}
                <div class="projects-empty">

                    <div class="projects-empty-icon">
                        +
                    </div>

                    <h2>
                        Todavía no hay proyectos
                    </h2>

                    <p>
                        Crea tu primer proyecto para empezar a producir un vídeo.
                    </p>

                    <a
                        href="{{ route('projects.create') }}"
                        class="ui-btn ui-btn-primary"
                    >
                        Crear primer proyecto
                    </a>

                </div>

            @endif

        </section>


        {{-- PAGINATION --}}
        @if($projects->hasPages())

            <div class="projects-pagination">
                {{ $projects->links() }}
            </div>

        @endif

    </div>

@endsection