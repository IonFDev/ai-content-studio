@extends('layouts.app')

@section('title', 'Dashboard · YouTube Studio')
@section('section', 'Dashboard')

@section('content')

    <div class="dashboard-page">

        {{-- =====================================================
             HEADER
        ====================================================== --}}

        <header class="dashboard-header">

            <div class="dashboard-intro">

                <span class="page-eyebrow">
                    Production workspace
                </span>

                <h1 class="dashboard-title">
                    Dashboard
                </h1>

                <p class="dashboard-description">
                    Una vista general de todo lo que está ocurriendo en tu pipeline de producción.
                </p>

            </div>

            <div class="dashboard-header-action">

                <a
                    href="{{ route('projects.create') }}"
                    class="ui-btn ui-btn-primary"
                >
                    <span class="ui-btn-icon">+</span>
                    Nuevo proyecto
                </a>

            </div>

        </header>


        {{-- =====================================================
             METRICS
        ====================================================== --}}

        <section class="dashboard-metrics">

            <article class="dashboard-metric dashboard-metric-featured">

                <div class="dashboard-metric-head">

                    <span class="dashboard-metric-label">
                        Proyectos
                    </span>

                    <span class="dashboard-metric-index">
                        01
                    </span>

                </div>

                <div class="dashboard-metric-value">
                    {{ $projectCount }}
                </div>

                <div class="dashboard-metric-footer">
                    Proyectos creados en el workspace
                </div>

            </article>


            <article class="dashboard-metric">

                <div class="dashboard-metric-head">

                    <span class="dashboard-metric-label">
                        Escenas
                    </span>

                    <span class="dashboard-metric-index">
                        02
                    </span>

                </div>

                <div class="dashboard-metric-value">
                    {{ $sceneCount }}
                </div>

                <div class="dashboard-metric-footer">
                    Unidades visuales generadas
                </div>

            </article>


            <article class="dashboard-metric">

                <div class="dashboard-metric-head">

                    <span class="dashboard-metric-label">
                        Imágenes
                    </span>

                    <span class="dashboard-metric-index">
                        03
                    </span>

                </div>

                <div class="dashboard-metric-value">
                    {{ $imageCount }}
                </div>

                <div class="dashboard-metric-footer">
                    Assets visuales generados
                </div>

            </article>

        </section>


        {{-- =====================================================
             RECENT PROJECTS
        ====================================================== --}}

        <section class="dashboard-projects-panel ui-panel">

            <div class="dashboard-panel-header">

                <div>

                    <span class="panel-eyebrow">
                        Recent work
                    </span>

                    <h2 class="panel-title">
                        Proyectos recientes
                    </h2>

                    <p class="panel-description">
                        Acceso rápido a los últimos proyectos modificados.
                    </p>

                </div>

                <a
                    href="{{ route('projects.index') }}"
                    class="dashboard-view-all"
                >
                    Ver todos
                    <svg viewBox="0 0 16 16" aria-hidden="true">
                        <path d="M5 2.75 10.25 8 5 13.25l1.5 1.5L13.25 8 6.5 1.25 5 2.75Z"/>
                    </svg>
                </a>

            </div>


            @if($projects->count())

                <div class="dashboard-project-list">

                    @foreach($projects as $project)

                        @php
                            $generatedImages = $project->generatedImageCount();
                            $sceneCount = (int) $project->scenes_count;

                            $progress = $sceneCount > 0
                                ? min(
                                    100,
                                    round(($generatedImages / $sceneCount) * 100)
                                )
                                : 0;
                        @endphp

                        <a
                            href="{{ route('projects.show', $project) }}"
                            class="dashboard-project-row"
                        >

                            {{-- INDEX --}}
                            <div class="dashboard-project-number">
                                {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}
                            </div>


                            {{-- PROJECT --}}
                            <div class="dashboard-project-main">

                                <div class="dashboard-project-name">
                                    {{ $project->name }}
                                </div>

                                <div class="dashboard-project-meta">

                                    <span>
                                        {{ $project->character?->name ?? 'Sin personaje' }}
                                    </span>

                                    <span class="dashboard-project-dot"></span>

                                    <span>
                                        {{ $project->updated_at->format('d/m/Y H:i') }}
                                    </span>

                                </div>

                            </div>


                            {{-- STATUS --}}
                            <div class="dashboard-project-status">

                                <x-status-badge
                                    :status="$project->status"
                                />

                            </div>


                            {{-- STATS --}}
                            <div class="dashboard-project-stats">

                                <div class="dashboard-project-stat">

                                    <span>
                                        Escenas
                                    </span>

                                    <strong>
                                        {{ $sceneCount }}
                                    </strong>

                                </div>

                                <div class="dashboard-project-stat">

                                    <span>
                                        Imágenes
                                    </span>

                                    <strong>
                                        {{ $generatedImages }}
                                    </strong>

                                </div>

                            </div>


                            {{-- PROGRESS --}}
                            <div class="dashboard-project-progress">

                                <div class="dashboard-progress-head">

                                    <span>
                                        Assets
                                    </span>

                                    <strong>
                                        {{ $progress }}%
                                    </strong>

                                </div>

                                <div class="dashboard-progress-track">

                                    <span
                                        class="dashboard-progress-fill"
                                        style="width: {{ $progress }}%"
                                    ></span>

                                </div>

                            </div>


                            {{-- ACTION --}}
                            <div class="dashboard-project-arrow">

                                <svg
                                    viewBox="0 0 16 16"
                                    aria-hidden="true"
                                >
                                    <path d="M5 2.75 10.25 8 5 13.25l1.5 1.5L13.25 8 6.5 1.25 5 2.75Z"/>
                                </svg>

                            </div>

                        </a>

                    @endforeach

                </div>

            @else

                <div class="dashboard-empty">

                    <div class="dashboard-empty-mark">
                        00
                    </div>

                    <h3>
                        Tu workspace está vacío
                    </h3>

                    <p>
                        Crea el primer proyecto para empezar a construir tu pipeline de producción.
                    </p>

                    <a
                        href="{{ route('projects.create') }}"
                        class="ui-btn ui-btn-primary"
                    >
                        Crear proyecto
                    </a>

                </div>

            @endif

        </section>


        {{-- =====================================================
             WORKSPACE FOOTER
        ====================================================== --}}

        <div class="dashboard-footer">

            <div class="dashboard-footer-label">
                YouTube Studio
            </div>

            <div class="dashboard-footer-copy">
                Local production workspace
            </div>

            <div class="dashboard-footer-status">
                <span></span>
                Operativo
            </div>

        </div>

    </div>

@endsection