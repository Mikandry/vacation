# VACATION v1.0 — mise à jour

Auteur : RAMAROSON Andry Michael

## Installation de la mise à jour (Mac / Linux)

Placez-vous à la racine de votre projet Laravel (le dossier qui contient `artisan`), puis :

```bash
unzip -o ~/Downloads/Vacation_v1.0_mise_a_jour.zip -d .
rm -f public/favicon.ico
php artisan migrate
php artisan optimize:clear
php artisan serve
```

Windows : décompressez le zip dans le dossier du projet en acceptant de remplacer les fichiers, supprimez `public/favicon.ico`, puis lancez `php artisan migrate`, `php artisan optimize:clear` et `php artisan serve`.

Ensuite, ouvrez **Paramètres** et cliquez une fois sur **Enregistrer** (crée les nouveaux paramètres).

## Contenu de la mise à jour

Nouveaux fichiers :
- config/vacation.php (nom, version, auteur)
- public/images/logo-madagascar.png (logo officiel), public/images/logo-app.svg, public/favicon.svg
- app/Services/IssueService.php, app/Http/Controllers/IssueController.php (pages « Voir / résoudre »)
- resources/views/issues/show.blade.php, resources/views/aide.blade.php

Fichiers remplacés :
- routes/web.php
- app/Models/Setting.php, app/Support/Logo.php
- app/Http/Controllers/SettingController.php
- app/Services/BalanceService.php, app/Services/DocumentExporter.php
- resources/views/layouts/app.blade.php, dashboard.blade.php, settings.blade.php
- resources/views/docs/decision, note, presence, etat, decompte, recap (.blade.php)

## Ce qui change
1. Tableau de bord : chaque ligne du tableau « Contrôles » a un bouton « Voir / résoudre » qui ouvre la liste précise des problèmes, avec les liens pour corriger.
2. Le logo de Madagascar est tout en haut de TOUS les documents (PDF, Word, Excel). Paramètres > « Afficher le logo ».
3. État de décompte et état de vacations : bloc ministère / secrétariat / direction à gauche, comme la présence.
4. Nouvelle interface « Vacation » : menu latéral, logo, tableau de bord refait, page « Aide et à propos » (v1.0, RAMAROSON Andry Michael).

Si vous avez modifié vous-même l'un des fichiers remplacés, faites-en une copie avant de décompresser.
