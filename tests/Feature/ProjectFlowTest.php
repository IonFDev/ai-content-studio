<?php
namespace Tests\Feature;
use App\Models\Character;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ProjectFlowTest extends TestCase { use RefreshDatabase; public function test_project_can_be_created(): void { $character=Character::create(['name'=>'Detective Stickman']); $response=$this->post(route('projects.store'),['name'=>'Why Is Gold Rising?','source_url'=>'https://www.youtube.com/watch?v=abc123','source_title'=>'Gold','character_id'=>$character->id]); $response->assertRedirect(); $this->assertDatabaseHas('projects',['name'=>'Why Is Gold Rising?','status'=>'source_ready']); } }
