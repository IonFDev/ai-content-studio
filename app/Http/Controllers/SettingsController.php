<?php
namespace App\Http\Controllers;
class SettingsController extends Controller { public function index() { return view('settings.index',['providers'=>['ai'=>config('youtube_studio.providers.ai'),'image'=>config('youtube_studio.providers.image'),'transcription'=>config('youtube_studio.providers.transcription')]]); } }
