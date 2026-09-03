<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Character extends Model { use HasFactory; protected $fillable=['name','description','reference_sheet_path','portrait_reference_path']; public function projects(): HasMany { return $this->hasMany(Project::class); } }
