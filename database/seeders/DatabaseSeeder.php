<?php
namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Setting;
use App\Models\Visa;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Setting::put(Setting::DEFAULTS);
        $bepc = "du Brevet d’Etudes du Premier Cycle de l’enseignement secondaire (BEPC/2nde)";
        $cepe = "du Certificat d’Etudes Primaires Elémentaires (CEPE)";
        // code, nom, intitulé, jours, taux, décret, quota direction
        $acts = [
            ['QUARANTAINE', 'Quarantaine', "quarantaine des sujets d’examen $bepc", 30, 30000, 223, 32],
            ['FEUILLES BEPC', 'FEUILLES BEPC', "répartition et envoi des feuilles d’examen $bepc", 15, 25000, 135, 80],
            ['ENVELOPPES', 'Enveloppes', "mise sous enveloppes des sujets d’examen $bepc", 15, 25000, 218, 66],
            ['DISPATCHING', 'Dispatching', "dispatching des sujets d’examen $bepc", 10, 36000, 80, 16],
            ['SESSION CEPE', 'Supervision de la session et de la correction CEPE', "supervision de la session et de la correction $cepe", 10, 36000, 230, 0],
            ['SESSION BEPC', 'Supervision de la session et de la correction BEPC', "supervision de la session et de la correction $bepc", 10, 36000, 230, 0],
            ['PREP. SUJETS', 'Supervision préparation des sujets', "supervision de la préparation des sujets $cepe", 7, 36000, 230, 18],
            ['ELAB. REG. CEPE', 'Supervision Elaboration Régionale CEPE', "suivi de l’élaboration régionale des sujets $cepe", 5, 36000, 46, 19],
        ];
        foreach ($acts as $i => [$code, $name, $title, $days, $rate, $dec, $quota])
            Activity::updateOrCreate(['code' => $code], compact('name', 'title', 'days', 'rate', 'quota') + ['decree_total' => $dec, 'position' => $i + 1, 'active' => true]);

        // examens : rattachement des activités d'après leur intitulé (modifiable ensuite dans le menu « Examens »)
        foreach ([['BEPC', $bepc, 1], ['CEPE', $cepe, 2]] as [$ecode, $ename, $epos]) {
            $exam = \App\Models\Exam::firstOrCreate(['code' => $ecode], ['name' => $ename, 'position' => $epos]);
            \App\Models\Activity::whereNull('exam_id')->where('title', 'like', "%($ecode%")->update(['exam_id' => $exam->id]);
        }

        if (Visa::count() === 0) {
            $visas = [
"Vu la Constitution ;",
"Vu le Décision n° 10-HCC/D3 du 14 Octobre 2025 accordant à l’autorité militaire compétente, représentée par le Colonel RANDRIANIRINA Michaël, l’exercice des fonctions de Chef de l’Etat ;",
"Vu la Loi Organique n° 2004-007 du 26 juillet 2004 sur les Lois de Finances ;",
"Vu la Loi n°021/2025 du 20 Décembre 2025 portant Loi de Finances Initiale pour 2026 ;",
"Vu le Décret n° 2004-571 du 1er Juin 2004 définissant les attributions et la responsabilité de l'ordonnateur dans les phases d'exécution de la Dépense publique ;",
"Vu le Décret n°2005-003 du 04 janvier 2005 portant règlement général sur la comptabilité de l’exécution budgétaire des organismes publics ;",
"Vu le Décret n°2005-089 du 15 février 2005 fixant la nomenclature des pièces justificatives des dépenses ;",
"Vu le Décret n°2005-210 du 26 Avril 2005 portant approbation du Plan Comptable des Opérations Publiques PCOP 2006, modifié par le Décret n°2007-863 du 04 Octobre 2007 portant aménagement du plan comptable des opérations publiques 2006 ;",
"Vu le Décret n°2024-063 du 23 janvier 2024 : décret modifiant le décret n°2020-1025 du 26 Aout 2020 modifié et complété par les décrets n° 2021-547 du 19 Mai 2021 et fixant les attributions du Ministre de l’Education Nationale ainsi que l’organisation générale de son Ministère ;",
"Vu le décret n°2025-235 du 04 Mars 2025 fixant l’organisation et la rétribution des travaux portant sur les examens et concours officiels de l’enseignement général relevant du Ministère en charge de l’Education Nationale ;",
"Vu le Décret n° 2024-007 du 04 janvier 2024 portant nomination du Premier Ministre, Chef du Gouvernement ;",
"Vu le Décret n° 2024-020 du 14 Janvier 2024 portant nomination des membres du Gouvernement ;",
"Vu le Décret n° 2025-2123 du 23 Décembre 2025 portant répartition des crédits autorisés par la Loi 2025-021 du 20 Décembre 2025 portant Loi de Finance pour 2026 entre les différents institutions et départements ministériels de l’Etat ;",
"Vu l’Arrêté Interministériel n° 16166/2025 du 19 juin 2025 fixant les taux des rétributions des travaux portant sur les examens et concours officiels de l’enseignement général relevant du ministère en charge de l’Education nationale ;",
"Vu l'Arrêté n°3559/2025/MEN du 05 décembre 2025 Complété par l'arrêté n° 34858/2025/MEN du 05 décembre 2025 fixant les calendriers scolaires 2025-2026 relevant du Ministère de l'Education Nationale;",
"Vu l’Arrêté n°36107/2025 du 24 décembre 2025 portant ouverture des crédits au niveau du Budget d’exécution de la gestion 2026 du budget de l’Etat.",
"Vu l’Arrêté n° 366/2026-MEN du 13 janvier 2026 modifié et complété par l’arrêté n°1567/2026/MEN du 23 janvier 2026 fixant le calendrier, les dates d’ouvertures et de clôtures des registres d’inscription des examens relevant du Ministère de l’Education Nationale, session 2026 ;",
"Vu l’Arrêté N°3022/2026 du 16 février 2026 portant nomination d’Ordonnateurs Secondaires du Ministère de l’Education Nationale ;",
"Vu l’Arrêté N°3023/2026 du 16 février 2026 portant nomination des Gestionnaires d’Activités du Ministère de l’Education Nationale ;",
"Vu l’Arrêté n°007/2024 du 18 Janvier 2024 portant ouverture des crédits au niveau du Budget d’exécution de la gestion 2024 du budget de l’Etat."];
            foreach ($visas as $i => $t) Visa::create(['text' => $t, 'included' => true, 'position' => $i + 1]);
        }
    }
}
