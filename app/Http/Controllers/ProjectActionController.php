<?php
namespace App\Http\Controllers;
use App\Jobs\GenerateAllImagesJob;
use App\Jobs\GenerateContentJob;
use App\Jobs\GenerateSceneImageJob;
use App\Models\Project;
use App\Models\Scene;
use App\Services\ImageGenerationService;
use App\Services\StoryboardService;
use App\Services\TranscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
class ProjectActionController extends Controller {
 public function transcribe(Project $project, TranscriptionService $service): RedirectResponse { try { $service->transcribe($project); return back()->with('success','Transcripción generada con Whisper.'); } catch (\Throwable $e) { $project->update(['status'=>'error']); report($e); return back()->with('error','No se pudo transcribir: '.$e->getMessage()); } }
 public function generateContent(Project $project): RedirectResponse { GenerateContentJob::dispatchSync($project->id); return back()->with('success','Contenido generado.'); }
 public function generateStoryboard(Project $project, StoryboardService $service): RedirectResponse { $service->generate($project); return back()->with('success','Storyboard generado.'); }
 public function generateAllImages(Project $project): RedirectResponse { GenerateAllImagesJob::dispatchSync($project->id); return back()->with('success','Imágenes generadas.'); }
 public function generateImage(Project $project, Scene $scene): RedirectResponse { abort_unless($scene->project_id===$project->id,404); GenerateSceneImageJob::dispatchSync($scene->id); return back()->with('success',sprintf('Imagen de la escena %02d regenerada.',$scene->order)); }
 public function updateYoutube(Request $request, Project $project): RedirectResponse { $data=$request->validate(['youtube_title'=>['nullable','string','max:255'],'youtube_description'=>['nullable','string'],'youtube_keywords'=>['nullable','string'],'youtube_hashtags'=>['nullable','string'],'thumbnail_idea'=>['nullable','string'],'thumbnail_text'=>['nullable','string','max:255']]); $data['youtube_keywords']=$this->splitList($data['youtube_keywords']??''); $data['youtube_hashtags']=$this->splitList($data['youtube_hashtags']??''); $project->update($data); return back()->with('success','Datos de YouTube guardados.'); }
 public function saveTranscript(Request $request, Project $project): RedirectResponse { $data=$request->validate(['transcript'=>['required','string']]); $project->update($data+['status'=>'transcript_ready']); return back()->with('success','Transcripción guardada.'); }
 public function saveScript(Request $request, Project $project): RedirectResponse { $data=$request->validate(['script'=>['required','string']]); $project->update($data+['status'=>'script_ready']); return back()->with('success','Guion guardado.'); }
 public function image(Project $project, Scene $scene) { abort_unless($scene->project_id===$project->id && $scene->image_path && Storage::disk('local')->exists($scene->image_path),404); return response()->file(Storage::disk('local')->path($scene->image_path)); }
 public function reorder(
    Request $request,
    Project $project,
    StoryboardService $service
): RedirectResponse {
    $data = $request->validate([
        'orders' => ['required', 'array'],
        'orders.*' => ['integer'],
    ]);

    $service->reorder($project, $data['orders']);

    return redirect()
        ->route('projects.show', [
            'project' => $project,
            'tab' => 'storyboard',
        ])
        ->with('success', 'Orden de escenas actualizado.');
}private function splitList(string $value): array { return array_values(array_filter(array_map(fn($item)=>trim($item),preg_split('/[,\\n]+/',$value)))); }
}
