<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('source_url', 2048)->nullable();
            $table->string('source_title')->nullable();
            $table->string('status')->default('draft')->index();
            $table->longText('transcript')->nullable();
            $table->longText('script')->nullable();
            $table->string('youtube_title')->nullable();
            $table->longText('youtube_description')->nullable();
            $table->json('youtube_keywords')->nullable();
            $table->json('youtube_hashtags')->nullable();
            $table->text('thumbnail_idea')->nullable();
            $table->string('thumbnail_text')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('projects'); }
};
