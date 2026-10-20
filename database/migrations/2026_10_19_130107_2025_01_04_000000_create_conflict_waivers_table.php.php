<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('conflict_waivers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('activity_a_id');   // toujours le plus petit id
            $t->unsignedBigInteger('activity_b_id');
            $t->unique(['agent_id', 'activity_a_id', 'activity_b_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('conflict_waivers'); }
};