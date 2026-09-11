<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scenes', function (Blueprint $table) {
            $table->json('manual_elements')->nullable()->after('image_prompt');
            $table->text('animation_notes')->nullable()->after('manual_elements');
            $table->text('production_notes')->nullable()->after('animation_notes');
        });
    }

    public function down(): void
    {
        Schema::table('scenes', function (Blueprint $table) {
            $table->dropColumn([
                'manual_elements',
                'animation_notes',
                'production_notes',
            ]);
        });
    }
};
