<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $t) {
            // type de sous-groupes : vide = automatique (d'après le nom), NONE, QUARANTAINE, DISPATCHING, CORRECTION
            $t->string('group_type', 20)->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('activities', fn(Blueprint $t) => $t->dropColumn('group_type'));
    }
};
