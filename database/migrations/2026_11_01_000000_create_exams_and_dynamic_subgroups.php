<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        // ---- examens (BEPC, CEPE, CAP/EP…)
        Schema::create('exams', function (Blueprint $t) {
            $t->id();
            $t->string('code', 40);                     // ex. BEPC
            $t->string('name');                         // ex. Brevet d'Etudes du Premier Cycle…
            $t->string('session')->nullable();          // remplace la session des paramètres
            $t->text('ref_arrete')->nullable();         // remplace la référence de l'arrêté des taux
            $t->string('decision_number')->nullable();  // remplace le n° de décision
            $t->unsignedInteger('position')->default(0);
            $t->timestamps();
        });
        Schema::table('activities', function (Blueprint $t) {
            $t->unsignedBigInteger('exam_id')->nullable()->index();
        });

        // ---- sous-groupes libres, propres à chaque activité
        Schema::create('activity_subgroups', function (Blueprint $t) {
            $t->id();
            $t->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->unsignedInteger('planned')->default(0);   // effectif prévu, 0 = pas de limite
            $t->unsignedInteger('position')->default(0);
        });

        $this->migrateExistingData();
    }

    /** Reprend les données existantes : examens devinés d'après les intitulés, sous-groupes fixes -> sous-groupes libres. */
    private function migrateExistingData(): void
    {
        $now = now();
        $exams = [
            'BEPC' => "Brevet d’Etudes du Premier Cycle de l’enseignement secondaire (BEPC/2nde)",
            'CEPE' => "Certificat d’Etudes Primaires Elémentaires (CEPE)",
            'CAP/EP' => "Certificat d’Aptitude Pédagogique dans les Ecoles Primaires (CAP/EP)",
        ];
        $examId = [];
        $acts = DB::table('activities')->get();
        foreach ($acts as $a) {
            $txt = mb_strtoupper($a->code . ' ' . $a->name . ' ' . $a->title);
            foreach ($exams as $code => $name) {
                if (str_contains($txt, $code)) {
                    if (!isset($examId[$code])) {
                        $examId[$code] = DB::table('exams')->insertGetId(['code' => $code, 'name' => $name, 'position' => count($examId) + 1, 'created_at' => $now, 'updated_at' => $now]);
                    }
                    DB::table('activities')->where('id', $a->id)->update(['exam_id' => $examId[$code]]);
                    break;
                }
            }
        }

        // anciens sous-groupes fixes (quarantaine, dispatching, correction)
        $kinds = [
            'QUARANTAINE' => ['OG' => 'Organisation générale', 'SUP' => 'Superviseur', 'COORD' => 'Coordonateur', 'SP' => 'Sous pli'],
            'DISPATCHING' => ['DISP' => 'Agent de dispatching', 'SECU' => 'Agent de sécurité'],
            'CORRECTION' => ['CORR' => 'Correcteur', 'SECR' => 'Agent de secrétariat'],
        ];
        $hasGroupType = Schema::hasColumn('activities', 'group_type');
        foreach ($acts as $a) {
            $gt = $hasGroupType ? (string) ($a->group_type ?? '') : '';
            $kind = null;
            if ($gt === 'NONE') $kind = null;
            elseif (isset($kinds[$gt])) $kind = $gt;
            else {
                $n = Str::lower(Str::ascii(trim((string) $a->name))); $c = Str::lower(Str::ascii(trim((string) $a->code)));
                if (str_contains($n . ' ' . $c, 'quarantaine')) $kind = 'QUARANTAINE';
                elseif (str_contains($n . ' ' . $c, 'dispatching')) $kind = 'DISPATCHING';
                elseif (str_starts_with($n, 'correction') || str_starts_with($c, 'correction')) $kind = 'CORRECTION';
            }
            if (!$kind) continue;
            $planned = (array) (json_decode((string) ($a->subgroups ?? ''), true) ?: []);
            $pos = 0;
            foreach ($kinds[$kind] as $key => $label) {
                $id = DB::table('activity_subgroups')->insertGetId(['activity_id' => $a->id, 'name' => $label, 'planned' => max(0, (int) ($planned[$key] ?? 0)), 'position' => ++$pos]);
                // les agents déjà répartis gardent leur rôle : l'ancienne clé (OG, SUP…) devient l'identifiant du nouveau sous-groupe
                if (Schema::hasTable('agent_subgroups')) {
                    DB::table('agent_subgroups')->where('activity_id', $a->id)->where('subgroup', $key)->update(['subgroup' => (string) $id]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_subgroups');
        Schema::table('activities', fn(Blueprint $t) => $t->dropColumn('exam_id'));
        Schema::dropIfExists('exams');
    }
};
