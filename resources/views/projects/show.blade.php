@extends('layouts.app') @section('title',$project->name.' · YouTube Studio') @section('content')
@php($tab=request('tab','summary'))
<div class="d-flex flex-wrap justify-content-between gap-3 align-items-start mb-4"><div><div class="d-flex align-items-center gap-2 mb-1"><h1 class="h3 mb-0">{{ $project->name }}</h1><x-status-badge :status="$project->status" /></div><div class="text-secondary small">{{ $project->character?->name ?? 'Sin personaje' }} · Actualizado {{ $project->updated_at->format('d/m/Y H:i') }}</div></div><div><a href="{{ route('projects.index') }}" class="btn btn-outline-secondary btn-sm">Volver</a></div></div>
<ul class="nav nav-tabs mb-4"><li class="nav-item"><a class="nav-link {{ $tab==='summary'?'active':'' }}" href="{{ route('projects.show',$project) }}?tab=summary">Resumen</a></li><li class="nav-item"><a class="nav-link {{ $tab==='source'?'active':'' }}" href="{{ route('projects.show',$project) }}?tab=source">Fuente</a></li><li class="nav-item"><a class="nav-link {{ $tab==='script'?'active':'' }}" href="{{ route('projects.show',$project) }}?tab=script">Guion</a></li><li class="nav-item"><a class="nav-link {{ $tab==='storyboard'?'active':'' }}" href="{{ route('projects.show',$project) }}?tab=storyboard">Storyboard</a></li><li class="nav-item"><a class="nav-link {{ $tab==='youtube'?'active':'' }}" href="{{ route('projects.show',$project) }}?tab=youtube">YouTube</a></li><li class="nav-item"><a class="nav-link {{ $tab==='images'?'active':'' }}" href="{{ route('projects.show',$project) }}?tab=images">Imágenes</a></li></ul>
@if($tab==='summary')
<div class="row g-3"><div class="col-xl-8"><div class="card p-4 mb-3"><h2 class="h5">Resumen</h2><div class="row g-3 mt-1"><div class="col-md-6"><div class="text-secondary small">Vídeo fuente</div><a href="{{ $project->source_url }}" target="_blank" rel="noopener" class="text-break">{{ $project->source_title ?: $project->source_url }}</a></div><div class="col-md-3"><div class="text-secondary small">Escenas</div><strong>{{ $project->scenes_count }}</strong></div><div class="col-md-3"><div class="text-secondary small">Imágenes</div><strong>{{ $project->generatedImageCount() }} / {{ $project->scenes_count }}</strong></div></div></div><div class="card p-4"><h2 class="h5">Datos del proyecto</h2><form method="POST" action="{{ route('projects.update',$project) }}">@csrf @method('PUT')<div class="mb-3"><label class="form-label">Nombre</label><input class="form-control" name="name" value="{{ $project->name }}"></div><div class="mb-3"><label class="form-label">URL fuente</label><input class="form-control" name="source_url" value="{{ $project->source_url }}"></div><div class="mb-3"><label class="form-label">Título fuente</label><input class="form-control" name="source_title" value="{{ $project->source_title }}"></div><button class="btn btn-outline-primary">Guardar cambios</button></form></div></div>
<div class="col-xl-4"><div class="card p-4 sticky-actions"><h2 class="h5 mb-3">Acciones</h2><div class="d-grid gap-2"><form method="POST" action="{{ route('projects.transcribe',$project) }}">@csrf<button class="btn btn-outline-primary w-100" {{ blank($project->source_url)?'disabled':'' }}>Transcribir</button></form><form method="POST" action="{{ route('projects.generate-content',$project) }}">@csrf<button class="btn btn-primary w-100" {{ blank($project->transcript)?'disabled':'' }}>Generar contenido</button></form><form method="POST" action="{{ route('projects.generate-storyboard',$project) }}">@csrf<button class="btn btn-outline-primary w-100" {{ blank($project->script)?'disabled':'' }}>Generar storyboard</button></form><form method="POST" action="{{ route('projects.generate-images',$project) }}">@csrf<button class="btn btn-success w-100" {{ $project->scenes_count===0?'disabled':'' }}>Generar todas las imágenes</button></form></div><hr><div class="small text-secondary">Los proveedores activos son Fake. No se realizan llamadas a APIs externas.</div></div></div></div>
@elseif($tab==='source')
<div class="card p-4"><div class="mb-3"><label class="form-label">URL YouTube</label><input class="form-control" value="{{ $project->source_url }}" readonly></div><div class="mb-3"><label class="form-label">Título fuente</label><input class="form-control" value="{{ $project->source_title }}" readonly></div><form method="POST" action="{{ route('projects.transcript.save',$project) }}">@csrf<label class="form-label">Transcripción</label><textarea class="form-control mb-3" name="transcript" rows="18" required>{{ $project->transcript }}</textarea><button class="btn btn-primary">Guardar transcripción</button></form></div>
@elseif($tab==='script')
<div class="card p-4"><form method="POST" action="{{ route('projects.script.save',$project) }}">@csrf<label class="form-label">Guion</label><textarea class="form-control mb-3" name="script" rows="22" required>{{ $project->script }}</textarea><button class="btn btn-primary">Guardar guion</button></form></div>
@elseif($tab==='storyboard')
<div class="d-flex justify-content-between align-items-center mb-3"><div><h2 class="h5 mb-1">Storyboard</h2><div class="text-secondary small">{{ $scenes->count() }} escenas · Arrastra para reordenar</div></div><div class="d-flex gap-2"> @if($scenes->count()) <a href="{{ route('projects.production-guide', $project) }}" target="_blank" class="btn btn-outline-dark" > Guía de producción </a> <form method="POST" action="{{ route('projects.scenes.reorder', $project) }}" id="scene-reorder-form" > @csrf <div id="scene-orders"></div> <button class="btn btn-outline-secondary"> Guardar orden </button> </form> <form method="POST" action="{{ route('projects.generate-images', $project) }}" > @csrf <button class="btn btn-success"> Generar todas </button> </form> @endif </div></div>
<div class="vstack gap-3" id="scene-list">@forelse($scenes as $scene)<div class="scene-card p-3" data-scene-id="{{ $scene->id }}"><div class="d-flex justify-content-between align-items-center mb-3"><div class="scene-number">SCENE {{ str_pad($scene->order,2,'0',STR_PAD_LEFT) }}</div><div class="d-flex gap-2 align-items-center"><x-status-badge :status="$scene->image_status" /><form method="POST" action="{{ route('projects.scenes.generate-image',[$project,$scene]) }}">@csrf<button class="btn btn-sm btn-outline-primary">{{ $scene->image_path?'Regenerar imagen':'Generar imagen' }}</button></form></div></div><div class="mb-3"><div class="small text-secondary fw-semibold">Narración</div><div class="mt-1">{{ $scene->narration }}</div></div><div class="mb-3"><div class="small text-secondary fw-semibold">Descripción visual</div><div class="mt-1">{{ $scene->visual_description }}</div></div><div><div class="small text-secondary fw-semibold">Image Prompt</div><div class="small bg-light rounded p-2 mt-1">{{ $scene->image_prompt }}</div></div></div>@empty<div class="card p-5 text-center text-secondary">Genera el storyboard para crear escenas.</div>@endforelse</div>
@if(!empty($scene->manual_elements))
    <div class="mb-3">
        <div class="small text-secondary fw-semibold">
            Producción manual
        </div>

        <div class="mt-2">
            @foreach($scene->manual_elements as $element)
                <div class="border rounded p-2 mb-2 bg-light">

                    <div class="small fw-semibold">
                        {{ strtoupper($element['type'] ?? 'ELEMENTO') }}
                    </div>

                    <div>
                        {{ $element['description'] ?? '' }}
                    </div>

                    @if(!empty($element['details']))
                        <div class="small text-secondary mt-1">
                            {{ $element['details'] }}
                        </div>
                    @endif

                    @if(!empty($element['position']))
                        <div class="small text-secondary mt-1">
                            Posición: {{ $element['position'] }}
                        </div>
                    @endif

                </div>
            @endforeach
        </div>
    </div>
@endif

@if($scene->animation_notes)
    <div class="mb-3">
        <div class="small text-secondary fw-semibold">
            Animación
        </div>

        <div class="small mt-1" style="white-space: pre-line;">
            {{ $scene->animation_notes }}
        </div>
    </div>
@endif

@if($scene->production_notes)
    <div>
        <div class="small text-secondary fw-semibold">
            Notas de producción
        </div>

        <div class="small mt-1">
            {{ $scene->production_notes }}
        </div>
    </div>
@endif
    @elseif($tab==='youtube')
<div class="card p-4"><form method="POST" action="{{ route('projects.youtube.update',$project) }}">@csrf<div class="mb-3"><label class="form-label">Título</label><div class="input-group"><input id="yt-title" name="youtube_title" class="form-control" value="{{ $project->youtube_title }}"><button type="button" class="btn btn-outline-secondary" data-copy-target="#yt-title">Copiar</button></div></div><div class="mb-3"><label class="form-label">Descripción</label><div class="input-group"><textarea id="yt-description" name="youtube_description" class="form-control">{{ $project->youtube_description }}</textarea><button type="button" class="btn btn-outline-secondary" data-copy-target="#yt-description">Copiar</button></div></div><div class="mb-3"><label class="form-label">Keywords</label><div class="input-group"><textarea id="yt-keywords" name="youtube_keywords" class="form-control">{{ implode(', ', $project->youtube_keywords ?? []) }}</textarea><button type="button" class="btn btn-outline-secondary" data-copy-target="#yt-keywords">Copiar</button></div></div><div class="mb-3"><label class="form-label">Hashtags</label><div class="input-group"><textarea id="yt-hashtags" name="youtube_hashtags" class="form-control">{{ implode(', ', $project->youtube_hashtags ?? []) }}</textarea><button type="button" class="btn btn-outline-secondary" data-copy-target="#yt-hashtags">Copiar</button></div></div><div class="mb-3"><label class="form-label">Idea de thumbnail</label><textarea name="thumbnail_idea" class="form-control">{{ $project->thumbnail_idea }}</textarea></div><div class="mb-4"><label class="form-label">Texto de thumbnail</label><input name="thumbnail_text" class="form-control" value="{{ $project->thumbnail_text }}"></div><button class="btn btn-primary">Guardar datos de YouTube</button></form></div>
@elseif($tab==='images')
<div class="d-flex justify-content-between align-items-center mb-3"><div><h2 class="h5 mb-1">Galería</h2><div class="text-secondary small">{{ $project->generatedImageCount() }} de {{ $scenes->count() }} imágenes generadas</div></div>@if($scenes->count())<form method="POST" action="{{ route('projects.generate-images',$project) }}">@csrf<button class="btn btn-success">Generar todas</button></form>@endif</div><div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-3">@forelse($scenes as $scene)<div class="col"><div class="card h-100 overflow-hidden">@if($scene->image_path)<img src="{{ route('projects.scenes.image',[$project,$scene]) }}" class="gallery-image" alt="Scene {{ $scene->order }}">@else<div class="image-placeholder">Sin imagen</div>@endif<div class="card-body"><div class="d-flex justify-content-between align-items-center"><strong>Scene {{ str_pad($scene->order,2,'0',STR_PAD_LEFT) }}</strong><x-status-badge :status="$scene->image_status" /></div><div class="d-flex gap-2 mt-3">@if($scene->image_path)<a class="btn btn-sm btn-outline-secondary" target="_blank" href="{{ route('projects.scenes.image',[$project,$scene]) }}">Ver</a>@endif<form method="POST" action="{{ route('projects.scenes.generate-image',[$project,$scene]) }}">@csrf<button class="btn btn-sm btn-outline-primary">Regenerar</button></form></div></div></div></div>@empty<div class="col-12"><div class="card p-5 text-center text-secondary">No hay escenas todavía.</div></div>@endforelse</div>
@endif
@endsection
