<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scenes', function (Blueprint $table) {
            $table->string('character_role')
                ->default('none')
                ->after('visual_description');

            $table->string('shot_type')
                ->default('wide')
                ->after('character_role');

            $table->text('visual_metaphor')
                ->nullable()
                ->after('shot_type');

            $table->json('visual_priority')
                ->nullable()
                ->after('visual_metaphor');
        });
    }

    public function down(): void
    {
        Schema::table('scenes', function (Blueprint $table) {
            $table->dropColumn([
                'character_role',
                'shot_type',
                'visual_metaphor',
                'visual_priority',
            ]);
        });
    }
};
