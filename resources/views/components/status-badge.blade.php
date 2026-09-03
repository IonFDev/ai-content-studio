@php($map=['draft'=>'secondary','source_ready'=>'info','transcript_ready'=>'primary','script_ready'=>'primary','storyboard_ready'=>'warning','images_generating'=>'warning','images_ready'=>'success','completed'=>'success','error'=>'danger'])
<span class="badge text-bg-{{ $map[$status] ?? 'secondary' }} status-badge">{{ str_replace('_',' ',$status) }}</span>
