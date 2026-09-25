@extends('layouts.app')

@section('title', 'Personajes · YouTube Studio')
@section('section', 'Personajes')

@section('breadcrumbs')
    <span>Personajes</span>
@endsection

@section('content')

    <div class="characters-page">

        {{-- HEADER --}}
        <header class="characters-header">

            <div>

                <span class="page-eyebrow">
                    Character library
                </span>

                <h1 class="page-title">
                    Personajes
                </h1>

                <p class="page-description">
                    Referencias maestras reutilizables para mantener una identidad visual consistente.
                </p>

            </div>

            <div class="characters-header-action">

                <a
                    href="{{ route('characters.create') }}"
                    class="ui-btn ui-btn-primary"
                >
                    <span class="ui-btn-icon">+</span>
                    Nuevo personaje
                </a>

            </div>

        </header>


        {{-- CONTENT --}}
        @if($characters->count())

            <section class="characters-grid">

                @foreach($characters as $character)

                    <article class="character-card">

                        {{-- IMAGE --}}
                        <a
                            href="{{ route('characters.edit', $character) }}"
                            class="character-card-image-link"
                        >

                            <div class="character-card-image">

                                @if($character->portrait_reference_path)

                                    <img
                                        src="{{ route('characters.image', [$character, 'portrait']) }}"
                                        alt="{{ $character->name }}"
                                        class="character-image"
                                    >

                                    <div class="character-image-overlay">

                                        <span>
                                            Editar personaje
                                        </span>

                                        <svg
                                            viewBox="0 0 16 16"
                                            aria-hidden="true"
                                        >
                                            <path d="M5 2.75 10.25 8 5 13.25l1.5 1.5L13.25 8 6.5 1.25 5 2.75Z"/>
                                        </svg>

                                    </div>

                                @else

                                    <div class="character-image-empty">

                                        <div class="character-image-empty-mark">
                                            ?
                                        </div>

                                        <span>
                                            Sin retrato
                                        </span>

                                    </div>

                                @endif

                            </div>

                        </a>


                        {{-- BODY --}}
                        <div class="character-card-body">

                            <div class="character-card-heading">

                                <div>

                                    <h2 class="character-card-name">
                                        {{ $character->name }}
                                    </h2>

                                    <div class="character-card-label">
                                        Character
                                    </div>

                                </div>

                                <span class="character-card-index">
                                    {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}
                                </span>

                            </div>


                            @if($character->description)

                                <p class="character-card-description">
                                    {{ $character->description }}
                                </p>

                            @else

                                <p class="character-card-description character-card-description-empty">
                                    Sin descripción.
                                </p>

                            @endif


                            {{-- META --}}
                            <div class="character-card-meta">

                                <div class="character-meta-item">

                                    <span>
                                        Proyectos
                                    </span>

                                    <strong>
                                        {{ $character->projects_count }}
                                    </strong>

                                </div>


                                <div class="character-meta-divider"></div>


                                <div class="character-meta-item">

                                    <span>
                                        Referencias
                                    </span>

                                    <strong>
                                        @if($character->reference_sheet_path && $character->portrait_reference_path)
                                            2 / 2
                                        @elseif($character->reference_sheet_path || $character->portrait_reference_path)
                                            1 / 2
                                        @else
                                            0 / 2
                                        @endif
                                    </strong>

                                </div>

                            </div>


                            {{-- FOOTER --}}
                            <div class="character-card-footer">

                                <span class="character-reference-state">

                                    <span
                                        class="character-reference-dot
                                        {{ ($character->reference_sheet_path && $character->portrait_reference_path)
                                            ? 'ready'
                                            : 'incomplete' }}"
                                    ></span>

                                    @if($character->reference_sheet_path && $character->portrait_reference_path)
                                        Referencias completas
                                    @elseif($character->reference_sheet_path || $character->portrait_reference_path)
                                        Referencias incompletas
                                    @else
                                        Sin referencias
                                    @endif

                                </span>


                                <a
                                    href="{{ route('characters.edit', $character) }}"
                                    class="character-card-edit"
                                    aria-label="Editar {{ $character->name }}"
                                >
                                    Editar

                                    <svg
                                        viewBox="0 0 16 16"
                                        aria-hidden="true"
                                    >
                                        <path d="M5 2.75 10.25 8 5 13.25l1.5 1.5L13.25 8 6.5 1.25 5 2.75Z"/>
                                    </svg>

                                </a>

                            </div>

                        </div>

                    </article>

                @endforeach

            </section>

        @else

            {{-- EMPTY STATE --}}
            <section class="characters-empty">

                <div class="characters-empty-mark">
                    00
                </div>

                <span class="page-eyebrow">
                    Character library
                </span>

                <h2>
                    Todavía no hay personajes
                </h2>

                <p>
                    Crea el primer personaje para definir las referencias visuales
                    que utilizará el sistema de generación.
                </p>

                <a
                    href="{{ route('characters.create') }}"
                    class="ui-btn ui-btn-primary"
                >
                    Crear personaje
                </a>

            </section>

        @endif

    </div>

@endsection