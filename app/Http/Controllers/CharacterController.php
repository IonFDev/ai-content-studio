<?php
namespace App\Http\Controllers;
use App\Models\Character;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
class CharacterController extends Controller {
 public function index() { return view('characters.index',['characters'=>Character::withCount('projects')->orderBy('name')->get()]); }
 public function create() { return view('characters.create'); }
 public function store(Request $request): RedirectResponse { $data=$request->validate(['name'=>['required','string','max:255'],'description'=>['nullable','string'],'reference_sheet'=>['nullable','image','mimes:png,jpg,jpeg,webp','max:10240'],'portrait_reference'=>['nullable','image','mimes:png,jpg,jpeg,webp','max:10240']]); $character=Character::create(['name'=>$data['name'],'description'=>$data['description']??null]); $this->storeReference($request,$character,'reference_sheet','reference_sheet_path'); $this->storeReference($request,$character,'portrait_reference','portrait_reference_path'); return redirect()->route('characters.index')->with('success','Personaje creado.'); }
 public function edit(Character $character) { return view('characters.edit',compact('character')); }
 public function update(Request $request, Character $character): RedirectResponse { $data=$request->validate(['name'=>['required','string','max:255'],'description'=>['nullable','string'],'reference_sheet'=>['nullable','image','mimes:png,jpg,jpeg,webp','max:10240'],'portrait_reference'=>['nullable','image','mimes:png,jpg,jpeg,webp','max:10240']]); $character->update(['name'=>$data['name'],'description'=>$data['description']??null]); $this->storeReference($request,$character,'reference_sheet','reference_sheet_path'); $this->storeReference($request,$character,'portrait_reference','portrait_reference_path'); return back()->with('success','Personaje actualizado.'); }
 public function destroy(Character $character): RedirectResponse { if($character->projects()->exists()) return back()->with('error','No se puede eliminar un personaje que está asociado a proyectos.'); foreach([$character->reference_sheet_path,$character->portrait_reference_path] as $path) if($path) Storage::disk('local')->delete($path); $character->delete(); return redirect()->route('characters.index')->with('success','Personaje eliminado.'); }
 public function image(Character $character,string $type) { $path=match($type){'sheet'=>$character->reference_sheet_path,'portrait'=>$character->portrait_reference_path,default=>null}; abort_unless($path && Storage::disk('local')->exists($path),404); return response()->file(Storage::disk('local')->path($path)); }
 private function storeReference(Request $request,Character $character,string $field,string $column): void { if(!$request->hasFile($field)) return; if($character->{$column}) Storage::disk('local')->delete($character->{$column}); $path=$request->file($field)->store("characters/{$character->id}",'local'); $character->update([$column=>$path]); }
}
