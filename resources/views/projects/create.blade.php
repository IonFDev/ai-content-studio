@extends('layouts.app') @section('title','Nuevo proyecto · YouTube Studio') @section('content')
<div class="mb-4"><h1 class="h3 mb-1">Nuevo proyecto</h1><p class="text-secondary mb-0">Crea el espacio de trabajo para un nuevo vídeo.</p></div>
<div class="row"><div class="col-xl-8"><div class="card"><div class="card-body"><form method="POST" action="{{ route('projects.store') }}">@csrf
<div class="mb-3"><label class="form-label">Nombre del proyecto</label><input name="name" value="{{ old('name') }}" class="form-control" required placeholder="Why Is Gold Rising?"></div>
<div class="mb-3"><label class="form-label">URL del vídeo de YouTube</label><input name="source_url" value="{{ old('source_url') }}" type="url" class="form-control" required placeholder="https://www.youtube.com/watch?v=..."></div>
<div class="mb-3"><label class="form-label">Título del vídeo fuente <span class="text-secondary">(opcional)</span></label><input name="source_title" value="{{ old('source_title') }}" class="form-control" placeholder="Título que aparece en YouTube"></div>
<div class="mb-4"><label class="form-label">Personaje</label><select name="character_id" class="form-select" required><option value="">Selecciona un personaje...</option>@foreach($characters as $character)<option value="{{ $character->id }}" @selected(old('character_id')==$character->id)>{{ $character->name }}</option>@endforeach</select></div>
<div class="d-flex justify-content-end gap-2"><a href="{{ route('projects.index') }}" class="btn btn-outline-secondary">Cancelar</a><button class="btn btn-primary">Crear proyecto</button></div>
</form></div></div></div></div>
@endsection
