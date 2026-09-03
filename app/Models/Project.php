<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Project extends Model {
 use HasFactory;
 public const STATUSES=['draft','source_ready','transcript_ready','script_ready','storyboard_ready','images_generating','images_ready','completed','error'];
 protected $fillable=['character_id','name','source_url','source_title','status','transcript','script','youtube_title','youtube_description','youtube_keywords','youtube_hashtags','thumbnail_idea','thumbnail_text'];
 protected function casts(): array { return ['youtube_keywords'=>'array','youtube_hashtags'=>'array']; }
 public function character(): BelongsTo { return $this->belongsTo(Character::class); }
 public function scenes(): HasMany { return $this->hasMany(Scene::class)->orderBy('order'); }
 public function generatedImageCount(): int { return $this->scenes()->where('image_status','generated')->count(); }
}
