<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $t) {
            // effectifs des sous-groupes (quarantaine) : {"OG":n,"SUP":n,"COORD":n,"SP":n}
            $t->text('subgroups')->nullable();
        });
        // Sous-groupe de chaque agent dans une activité. Table séparée de "assignments"
        // car la répartition automatique vide et recrée "assignments" à chaque calcul.
        Schema::create('agent_subgroups', function (Blueprint $t) {
            $t->id();
            $t->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $t->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $t->string('subgroup', 10);
            $t->unique(['agent_id', 'activity_id']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('agent_subgroups');
        Schema::table('activities', fn(Blueprint $t) => $t->dropColumn('subgroups'));
    }
};
