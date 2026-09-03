<?php
namespace App\Http\Controllers;
use App\Models\Character;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
class ProjectController extends Controller {
 public function index() { return view('projects.index',['projects'=>Project::with('character')->withCount('scenes')->latest()->paginate(12)]); }
 public function create() { return view('projects.create',['characters'=>Character::orderBy('name')->get()]); }
 public function store(Request $request, ProjectService $service): RedirectResponse { $data=$request->validate(['name'=>['required','string','max:255'],'source_url'=>['required','url','max:2048',function($attribute,$value,$fail){ if(!preg_match('~^https?://(www\\.)?(youtube\\.com/watch\\?v=|youtu\\.be/)~i',$value)) $fail('La URL debe pertenecer a YouTube.'); }],'source_title'=>['nullable','string','max:255'],'character_id'=>['required','exists:characters,id']]); $project=$service->create($data); return redirect()->route('projects.show',$project)->with('success','Proyecto creado correctamente.'); }
 public function show(Project $project) { $project->load('character'); $project->loadCount('scenes'); $scenes=$project->scenes()->get(); return view('projects.show',compact('project','scenes')); }
 public function update(Request $request, Project $project): RedirectResponse { $data=$request->validate(['name'=>['required','string','max:255'],'source_url'=>['required','url','max:2048'],'source_title'=>['nullable','string','max:255']]); $project->update($data); return back()->with('success','Proyecto actualizado.'); }
 public function destroy(Project $project): RedirectResponse { $project->delete(); return redirect()->route('projects.index')->with('success','Proyecto eliminado.'); }
}
