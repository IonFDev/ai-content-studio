<?php
namespace App\Http\Controllers;
use App\Models\Project;
class DashboardController extends Controller { public function __invoke() { return view('dashboard.index',['projectCount'=>Project::count(),'sceneCount'=>\App\Models\Scene::count(),'imageCount'=>\App\Models\Scene::where('image_status','generated')->count(),'projects'=>Project::with('character')->withCount('scenes')->latest()->limit(8)->get()]); } }
