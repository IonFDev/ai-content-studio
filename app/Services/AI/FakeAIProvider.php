<?php
namespace App\Services\AI;
use App\Contracts\AI\AIProvider;
use App\Models\Project;
class FakeAIProvider implements AIProvider {
 public function generateContent(Project $project, string $transcript): array {
  $topic = $project->source_title ?: $project->name;
  $scenes = [
   ['order'=>1,'narration'=>'El oro vuelve a estar en el centro de la conversación financiera. Pero la pregunta importante no es solo cuánto sube, sino qué está intentando decirnos el mercado.','visual_description'=>'Detective stickman frente a una gran pantalla financiera con una línea del oro ascendiendo y titulares económicos alrededor.','image_prompt'=>'16:9 editorial finance illustration, minimalist black stickman detective with white round head, black trench coat, black fedora and magnifying glass, examining a dramatic gold price chart on a large financial screen, cinematic lighting, clean composition, no readable text, consistent character design.'],
   ['order'=>2,'narration'=>'Uno de los motores clave son los tipos de interés. Cuando el mercado espera una política monetaria menos restrictiva, el coste de oportunidad de mantener oro cambia.','visual_description'=>'El detective compara una balanza entre un gráfico de tipos de interés y una moneda de oro.','image_prompt'=>'16:9 cinematic financial infographic scene, same minimalist black stickman detective character comparing interest-rate charts with a large gold coin on a balance scale, clean editorial composition, white round head, black trench coat and fedora, no readable text.'],
   ['order'=>3,'narration'=>'A eso se suma una tendencia menos visible: los bancos centrales están reforzando sus reservas de oro.','visual_description'=>'Bóvedas centrales con lingotes y varios bancos centrales representados de forma simbólica.','image_prompt'=>'16:9 cinematic vault scene, consistent minimalist black stickman detective observing central-bank style vaults filled with gold bars, subtle world map in background, sophisticated finance documentary aesthetic, no logos, no readable text.'],
   ['order'=>4,'narration'=>'Y existe un tercer elemento: la incertidumbre. Cuando aumenta el riesgo, algunos inversores buscan activos que no dependan directamente de una empresa o de una divisa concreta.','visual_description'=>'El detective observa un mapa mundial con zonas de incertidumbre mientras protege una moneda de oro bajo una lupa.','image_prompt'=>'16:9 dramatic world finance scene, minimalist black stickman detective holding a magnifying glass over a gold coin while a stylized world map and risk indicators surround him, cinematic shadows, clean high-contrast editorial art, no readable text.'],
   ['order'=>5,'narration'=>'Eso no significa que el oro solo pueda subir. Las expectativas pueden cambiar, el dólar puede fortalecerse y los inversores pueden volver a activos de riesgo.','visual_description'=>'El detective examina dos caminos: uno hacia el oro y otro hacia acciones y bonos.','image_prompt'=>'16:9 editorial crossroads metaphor in finance, same black stickman detective at a fork between a glowing gold path and a stock-market path, analytical mood, clean cinematic composition, white head, black trench coat and fedora, no text.'],
   ['order'=>6,'narration'=>'La conclusión es más interesante que una simple predicción de precio: el oro funciona como una señal de cómo el mercado percibe los tipos, el riesgo y la confianza.','visual_description'=>'Plano final del detective conectando tres grandes conceptos alrededor de una moneda de oro.','image_prompt'=>'16:9 final finance documentary composition, minimalist black stickman detective connecting interest rates, risk and confidence around a central gold coin, subtle charts in background, cinematic lighting, polished editorial illustration, no readable text.'],
  ];
  return [
   'youtube_title'=>"Why Is Gold Rising? The 3 Forces Behind the Move",
   'youtube_description'=>"El oro no se mueve por una sola razón. En este vídeo analizamos tres fuerzas que ayudan a explicar su fortaleza: tipos de interés, compras de bancos centrales y aversión al riesgo. Un análisis original basado en el tema del vídeo de referencia, no una reproducción de su guion.",
   'youtube_keywords'=>['gold','gold price','economy','interest rates','central banks','finance','investing','markets'],
   'youtube_hashtags'=>['#Gold','#Finance','#Economy','#Investing','#Markets'],
   'thumbnail_idea'=>'El detective señala una gran moneda de oro mientras detrás aparecen un gráfico ascendente y un banco central, con sensación de misterio financiero.',
   'thumbnail_text'=>'WHY IS GOLD RISING?',
   'script'=>"El oro vuelve a estar en el centro de la conversación financiera. Pero la pregunta importante no es solo cuánto sube, sino qué está intentando decirnos el mercado.\n\nEn este vídeo analizamos tres fuerzas: tipos de interés, compras de bancos centrales y aversión al riesgo. La idea es entender el mecanismo, no intentar adivinar el próximo precio.",
   'scenes'=>$scenes,
  ];
 }
}
