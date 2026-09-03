<?php
namespace Database\Seeders;
use App\Models\Character;
use App\Models\Project;
use App\Models\Scene;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
class DatabaseSeeder extends Seeder {
 public function run(): void {
  $character=Character::create(['name'=>'Detective Stickman','description'=>'Stickman minimalista negro: cabeza redonda blanca, ojos y boca expresivos, gabardina negra, fedora negro y lupa clásica.']);
  $projects=[
   ['name'=>'Why Is Gold Rising?','source_url'=>'https://www.youtube.com/watch?v=demo-gold','source_title'=>'Why Is Gold Rising?','status'=>'storyboard_ready'],
   ['name'=>'The Hidden Cost of High Interest Rates','source_url'=>'https://www.youtube.com/watch?v=demo-rates','source_title'=>'The Hidden Cost of High Interest Rates','status'=>'script_ready'],
  ];
  foreach($projects as $i=>$data){ $project=Project::create($data+['character_id'=>$character->id,'youtube_title'=>$data['name'],'youtube_keywords'=>['economy','finance','markets'],'youtube_hashtags'=>['#Finance','#Economy'],'script'=>'Guion de demostración para validar el flujo local.']); foreach(range(1,$i===0?6:3) as $order){ $scene=$project->scenes()->create(['order'=>$order,'narration'=>"Narración de prueba de la escena {$order}.",'visual_description'=>"Descripción visual de prueba para la escena {$order}.",'image_prompt'=>"Finance documentary illustration, Detective Stickman analysing scene {$order}, clean editorial composition.",'image_status'=>$i===0 && $order<=3?'generated':'pending']); if($scene->image_status==='generated'){ $source='resources/images/fake/'.sprintf('%02d.png',$order); $path="projects/{$project->id}/images/".str_pad((string)$order,3,'0',STR_PAD_LEFT).'.png'; Storage::disk('local')->put($path,file_get_contents($source)); $scene->update(['image_path'=>$path]); } } foreach(['source','transcript','script','storyboard','images','assets'] as $folder) Storage::disk('local')->makeDirectory("projects/{$project->id}/{$folder}"); }
 }
}
