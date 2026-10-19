<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->text('value')->nullable();
        });
        Schema::create('activities', function (Blueprint $t) {
            $t->id();
            $t->string('code');
            $t->string('name');
            $t->text('title')->nullable();            // intitulé repris dans la décision
            $t->unsignedSmallInteger('days')->default(1);
            $t->unsignedInteger('rate')->default(0);   // taux / jour (Ar)
            $t->unsignedInteger('quota')->default(0);  // nb d'agents de la direction
            $t->unsignedInteger('decree_total')->default(0);
            $t->date('start_date')->nullable();
            $t->unsignedInteger('position')->default(0); // ordre de traitement
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('agents', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('im')->nullable();
            $t->string('locality')->nullable();
            $t->string('type')->default('DIRECTION');  // DIRECTION | EXTERNE
            $t->boolean('available')->default(true);
            $t->string('corps')->nullable();
            $t->string('grade')->nullable();
            $t->string('indice')->nullable();
            $t->string('function')->nullable();
            $t->unsignedBigInteger('history_amount')->default(0);
            $t->unsignedInteger('draw_order')->default(0);
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->index('name');
        });
        Schema::create('exceptions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $t->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $t->char('mode', 1);                       // F = forcer, X = exclure
            $t->string('reason')->nullable();
            $t->unique(['agent_id', 'activity_id']);
        });
        Schema::create('assignments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $t->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $t->boolean('forced')->default(false);
            $t->unique(['agent_id', 'activity_id']);
        });
        Schema::create('visas', function (Blueprint $t) {
            $t->id();
            $t->text('text');
            $t->boolean('included')->default(true);
            $t->unsignedInteger('position')->default(0);
        });
    }
    public function down(): void
    {
        foreach (['assignments', 'exceptions', 'visas', 'agents', 'activities', 'settings'] as $t) Schema::dropIfExists($t);
    }
};
