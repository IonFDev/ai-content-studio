<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('scenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('order');
            $table->longText('narration');
            $table->text('visual_description')->nullable();
            $table->text('image_prompt')->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_status')->default('pending')->index();
            $table->timestamps();
            $table->unique(['project_id', 'order']);
        });
    }

    public function down(): void { Schema::dropIfExists('scenes'); }
};
