@php

    $statuses = [

        'draft' => [
            'label' => 'Borrador',
            'class' => 'status-neutral',
        ],

        'source_ready' => [
            'label' => 'Fuente lista',
            'class' => 'status-info',
        ],

        'transcript_ready' => [
            'label' => 'Transcripción lista',
            'class' => 'status-info',
        ],

        'script_ready' => [
            'label' => 'Guion listo',
            'class' => 'status-info',
        ],

        'storyboard_ready' => [
            'label' => 'Storyboard listo',
            'class' => 'status-warning',
        ],

        'images_generating' => [
            'label' => 'Generando imágenes',
            'class' => 'status-warning',
            'dot' => true,
        ],

        'images_ready' => [
            'label' => 'Imágenes listas',
            'class' => 'status-success',
        ],

        'completed' => [
            'label' => 'Completado',
            'class' => 'status-success',
        ],

        'error' => [
            'label' => 'Error',
            'class' => 'status-error',
        ],


        // Scene statuses

        'pending' => [
            'label' => 'Pendiente',
            'class' => 'status-neutral',
        ],

        'generating' => [
            'label' => 'Generando',
            'class' => 'status-warning',
            'dot' => true,
        ],

        'generated' => [
            'label' => 'Generada',
            'class' => 'status-success',
        ],

    ];

    $current = $statuses[$status] ?? [
        'label' => str_replace('_', ' ', ucfirst($status)),
        'class' => 'status-neutral',
    ];

@endphp


<span
    class="status-badge {{ $current['class'] }}"
    title="{{ $current['label'] }}"
>

    @if(!empty($current['dot']))
        <span class="status-badge-dot"></span>
    @endif

    <span class="status-badge-label">
        {{ $current['label'] }}
    </span>

</span>