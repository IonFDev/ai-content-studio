@extends('layouts.app')

@section('title', 'Configuración · YouTube Studio')
@section('section', 'Configuración')

@section('breadcrumbs')
    <span>Configuración</span>
@endsection

@section('content')

    <div class="settings-page">

        {{-- HEADER --}}
        <div class="settings-header">

            <div>

                <span class="page-eyebrow">
                    System
                </span>

                <h1 class="page-title">
                    Configuración
                </h1>

                <p class="page-description">
                    Proveedores y configuración del entorno de producción local.
                </p>

            </div>

        </div>


        {{-- PROVIDERS --}}
        <section class="ui-panel settings-panel">

            <div class="ui-panel-header">

                <div>

                    <span class="panel-eyebrow">
                        Providers
                    </span>

                    <h2 class="panel-title">
                        Servicios activos
                    </h2>

                    <p class="panel-description">
                        Proveedores utilizados actualmente por el pipeline de producción.
                    </p>

                </div>

                <div class="settings-environment">
                    <span class="settings-environment-dot"></span>
                    Entorno local
                </div>

            </div>


            <div class="provider-grid">

                {{-- AI --}}
                <article class="provider-card">

                    <div class="provider-card-top">

                        <div class="provider-icon provider-icon-ai">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 3a7 7 0 0 1 7 7c0 2.42-1.22 4.55-3.08 5.82-.69.47-1.1 1.24-1.1 2.08V19H9.18v-1.1c0-.84-.41-1.61-1.1-2.08A7 7 0 1 1 12 3Zm-2 18h4v1h-4v-1Z"/>
                            </svg>
                        </div>

                        <span class="provider-status">
                            Activo
                        </span>

                    </div>

                    <div class="provider-card-label">
                        AI Provider
                    </div>

                    <div class="provider-name">
                        {{ ucfirst($providers['ai']) }}
                    </div>

                    <div class="provider-description">
                        Generación de contenido, guion y estructura narrativa.
                    </div>

                </article>


                {{-- IMAGE --}}
                <article class="provider-card">

                    <div class="provider-card-top">

                        <div class="provider-icon provider-icon-image">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M5 4h14a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1Zm1 3v9.5l3.2-3.2a1 1 0 0 1 1.42 0L13 15.68l1.8-1.8a1 1 0 0 1 1.41 0L18 15.67V7H6Zm3 4a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/>
                            </svg>
                        </div>

                        <span class="provider-status">
                            Activo
                        </span>

                    </div>

                    <div class="provider-card-label">
                        Image Provider
                    </div>

                    <div class="provider-name">
                        {{ ucfirst($providers['image']) }}
                    </div>

                    <div class="provider-description">
                        Generación y regeneración de los assets visuales.
                    </div>

                </article>


                {{-- TRANSCRIPTION --}}
                <article class="provider-card">

                    <div class="provider-card-top">

                        <div class="provider-icon provider-icon-transcription">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 3a3 3 0 0 1 3 3v6a3 3 0 1 1-6 0V6a3 3 0 0 1 3-3Zm-5 9a5 5 0 0 0 10 0h2a7 7 0 0 1-6 6.92V21h-2v-2.08A7 7 0 0 1 5 12h2Z"/>
                            </svg>
                        </div>

                        <span class="provider-status">
                            Activo
                        </span>

                    </div>

                    <div class="provider-card-label">
                        Transcription Provider
                    </div>

                    <div class="provider-name">
                        {{ ucfirst($providers['transcription']) }}
                    </div>

                    <div class="provider-description">
                        Conversión del material fuente en texto utilizable.
                    </div>

                </article>

            </div>

        </section>


        {{-- CONFIGURATION --}}
        <div class="settings-info-grid">

            <section class="ui-panel settings-info-panel">

                <div class="ui-panel-header">

                    <div>

                        <span class="panel-eyebrow">
                            Configuration
                        </span>

                        <h2 class="panel-title">
                            Variables de entorno
                        </h2>

                    </div>

                </div>

                <div class="settings-info-content">

                    <div class="settings-info-row">

                        <div>
                            <span class="settings-info-label">
                                Credenciales
                            </span>

                            <span class="settings-info-value">
                                Variables de entorno
                            </span>
                        </div>

                        <span class="settings-info-badge">
                            .env
                        </span>

                    </div>


                    <div class="settings-info-row">

                        <div>
                            <span class="settings-info-label">
                                Configuración
                            </span>

                            <span class="settings-info-value">
                                config/services.php
                            </span>
                        </div>

                        <span class="settings-info-badge">
                            Laravel
                        </span>

                    </div>


                    <div class="settings-info-row">

                        <div>
                            <span class="settings-info-label">
                                Base de datos
                            </span>

                            <span class="settings-info-value">
                                Sin credenciales de proveedores
                            </span>
                        </div>

                        <span class="settings-info-badge">
                            Seguro
                        </span>

                    </div>

                </div>

            </section>


            <section class="settings-note">

                <div class="settings-note-mark">
                    i
                </div>

                <div>

                    <span class="settings-note-label">
                        Arquitectura
                    </span>

                    <p>
                        Los proveedores están desacoplados mediante interfaces.
                        Esto permite cambiar el servicio utilizado sin modificar
                        el resto del pipeline de producción.
                    </p>

                </div>

            </section>

        </div>

    </div>

@endsection