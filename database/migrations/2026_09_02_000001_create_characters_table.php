<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('characters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('reference_sheet_path')->nullable();
            $table->string('portrait_reference_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('characters'); }
};
