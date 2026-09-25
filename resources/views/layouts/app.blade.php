<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>@yield('title', 'YouTube Studio')</title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])
</head>

<body class="app-body">

<div class="app-shell">

    {{-- SIDEBAR --}}
    <aside class="app-sidebar">

        <div class="sidebar-inner">

            {{-- Brand --}}
            <a
                href="{{ route('dashboard') }}"
                class="brand"
            >
                <span class="brand-mark">YS</span>

                <span class="brand-copy">
                    <strong>YouTube Studio</strong>
                    <small>Local workspace</small>
                </span>
            </a>

            {{-- Navigation --}}
            <div class="sidebar-section">

                <div class="sidebar-label">
                    Workspace
                </div>

                <nav class="sidebar-nav">

                    <a
                        href="{{ route('dashboard') }}"
                        class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                    >
                        <span class="sidebar-icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M4 13h6V4H4v9Zm10 7h6v-9h-6v9ZM4 20h6v-3H4v3Zm10-16v3h6V4h-6Z"/>
                            </svg>
                        </span>

                        <span>Dashboard</span>
                    </a>

                    <a
                        href="{{ route('projects.index') }}"
                        class="sidebar-link {{ request()->routeIs('projects.*') ? 'active' : '' }}"
                    >
                        <span class="sidebar-icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M4 5.5A1.5 1.5 0 0 1 5.5 4h4l2 2h7A1.5 1.5 0 0 1 20 7.5v11A1.5 1.5 0 0 1 18.5 20h-13A1.5 1.5 0 0 1 4 18.5v-13Z"/>
                            </svg>
                        </span>

                        <span>Proyectos</span>
                    </a>

                    <a
                        href="{{ route('characters.index') }}"
                        class="sidebar-link {{ request()->routeIs('characters.*') ? 'active' : '' }}"
                    >
                        <span class="sidebar-icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 4a4 4 0 1 1 0 8 4 4 0 0 1 0-8Zm-7 15a7 7 0 0 1 14 0H5Z"/>
                            </svg>
                        </span>

                        <span>Personajes</span>
                    </a>

                    <a
                        href="{{ route('settings') }}"
                        class="sidebar-link {{ request()->routeIs('settings') ? 'active' : '' }}"
                    >
                        <span class="sidebar-icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="m9.25 4.1.5-1.1h4.5l.5 1.1 1.15.5 1.1-.5 3.2 3.2-.5 1.1.5 1.15 1.1.5v4.5l-1.1.5-.5 1.15.5 1.1-3.2 3.2-1.1-.5-1.15.5-.5 1.1h-4.5l-.5-1.1-1.15-.5-1.1.5-3.2-3.2.5-1.1-.5-1.15-1.1-.5v-4.5l1.1-.5.5-1.15-.5-1.1 3.2-3.2 1.1.5 1.15-.5ZM12 15.5A3.5 3.5 0 1 0 12 8a3.5 3.5 0 0 0 0 7.5Z"/>
                            </svg>
                        </span>

                        <span>Configuración</span>
                    </a>

                </nav>

            </div>

            {{-- Sidebar bottom --}}
            <div class="sidebar-footer">

                <div class="workspace-status">
                    <span class="status-dot"></span>

                    <div>
                        <strong>Entorno local</strong>
                        <span>Producción offline</span>
                    </div>
                </div>

            </div>

        </div>

    </aside>


    {{-- MAIN --}}
    <div class="app-main">

        {{-- TOPBAR --}}
        <header class="app-topbar">

            <div class="topbar-left">

                @hasSection('breadcrumbs')
                    <div class="page-breadcrumbs">
                        @yield('breadcrumbs')
                    </div>
                @else
                    <div class="page-context">
                        <span class="page-context-dot"></span>
                        <span>@yield('section', 'Workspace')</span>
                    </div>
                @endif

            </div>

            <div class="topbar-right">

                <span class="topbar-meta">
                    Local
                </span>

                <div class="topbar-divider"></div>

                <span class="topbar-meta topbar-meta-strong">
                    {{ now()->format('d/m/Y') }}
                </span>

            </div>

        </header>


        {{-- CONTENT --}}
        <main class="app-content">

            <div class="content-container">

                {{-- Success --}}
                @if(session('success'))
                    <div class="ui-alert ui-alert-success">
                        <span class="ui-alert-icon">✓</span>

                        <div class="ui-alert-content">
                            {{ session('success') }}
                        </div>

                        <button
                            type="button"
                            class="ui-alert-close"
                            data-bs-dismiss="alert"
                            aria-label="Cerrar"
                        >
                            ×
                        </button>
                    </div>
                @endif


                {{-- Error --}}
                @if(session('error'))
                    <div class="ui-alert ui-alert-error">
                        <span class="ui-alert-icon">!</span>

                        <div class="ui-alert-content">
                            {{ session('error') }}
                        </div>

                        <button
                            type="button"
                            class="ui-alert-close"
                            data-bs-dismiss="alert"
                            aria-label="Cerrar"
                        >
                            ×
                        </button>
                    </div>
                @endif


                {{-- Validation --}}
                @if($errors->any())
                    <div class="ui-alert ui-alert-error">

                        <span class="ui-alert-icon">!</span>

                        <div class="ui-alert-content">

                            <strong>Revisa el formulario.</strong>

                            <ul class="ui-alert-list">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>

                        </div>

                        <button
                            type="button"
                            class="ui-alert-close"
                            data-bs-dismiss="alert"
                            aria-label="Cerrar"
                        >
                            ×
                        </button>

                    </div>
                @endif


                @yield('content')

            </div>

        </main>

    </div>

</div>

</body>
</html>