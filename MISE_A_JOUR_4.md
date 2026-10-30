# VACATION — mise à jour 4 (cumulative) : examens + sous-groupes dynamiques

## Installation (à la racine du projet, là où se trouve `artisan`)
    unzip -o Vacation_maj_4.zip -d .
    php artisan migrate
    php artisan optimize:clear
Sauvegardez `database/database.sqlite` avant : la migration reprend vos données (voir ci-dessous).

## 1. Sous-groupes dynamiques (Activités > Modifier)
- Bloc « Sous-groupes de l'activité » : ajouter, renommer, monter/descendre, supprimer. Effectif prévu facultatif (0 = pas de limite).
- Boutons « Modèles » : Quarantaine, Dispatching, Correction (remplissent la liste, modifiable ensuite).
- Dès qu'une activité a des sous-groupes : décision, note, présence, état et décompte se divisent automatiquement (ordre = ordre de la liste).
- Sans sous-groupe : aucun changement.
- Choisir les agents : la liste des rôles vient des sous-groupes de l'activité.
- Reprise : quarantaine / dispatching / correction existants sont convertis, les rôles déjà attribués sont conservés.

## 2. Examens (menu Données > Examens)
- Un examen = code (BEPC, CEPE, CAP/EP…) + nom complet. Chaque activité peut être rattachée à un examen.
- Le code de l'examen s'ajoute au nom de l'activité dans les documents (« Quarantaine (BEPC) – Superviseur ») et aux noms de fichiers.
- Facultatif par examen : session, n° de décision, référence de l'arrêté des taux (remplacent les Paramètres ; vide = Paramètres).
- Dans l'intitulé d'une activité : {examen} = nom complet de l'examen, {examen_code} = son code. Jetons aussi disponibles dans les modèles de texte.
- Filtre par examen dans Activités et Documents.
- Reprise : les activités existantes sont rattachées à BEPC / CEPE / CAP/EP d'après leur intitulé (modifiable).
- L'équilibre des vacations reste calculé sur TOUS les examens ensemble.
