# Application Laravel — Répartition des agents, décisions, présences, états de vacations

Laravel 11 · PHP ≥ 8.2 · Bootstrap 5 + Chart.js (CDN) · PhpSpreadsheet (Excel) · PhpWord (Word) · DomPDF (PDF)

## Installation (10 minutes)

```bash
composer create-project laravel/laravel repartition
cd repartition
composer require phpoffice/phpspreadsheet phpoffice/phpword barryvdh/laravel-dompdf

# copier le contenu du dossier « app/ database/ resources/ routes/ » fourni PAR-DESSUS le projet
# (accepter d'écraser routes/web.php, Controller.php, DatabaseSeeder.php)

php artisan migrate --seed          # tables + 8 activités + 20 visas + paramètres par défaut
php artisan serve                   # http://127.0.0.1:8000
```

Base de données : SQLite par défaut (Laravel 11) ; pour MySQL, régler `.env` avant `migrate`.
Extensions PHP requises : `zip`, `gd`, `mbstring`, `xml`, `fileinfo`.

## Premiers pas
1. **Agents ➜ Importer Excel** : choisir `Repartition_Agents_DEXAMC_2026.xlsx` (feuille AGENTS) — importe les 163 lignes,
   les types DIRECTION/EXTERNE, la disponibilité, l'historique et les exceptions F/X par activité.
   (ou en ligne de commande : `php artisan repartition:import chemin/vers/fichier.xlsx`)
2. **Activités** : vérifier jours, taux, quotas, ordre de traitement (compléter SESSION CEPE / SESSION BEPC, quota 0 par défaut).
3. **Tableau de bord ➜ Relancer la répartition**.
4. **Documents** : décision, feuille de présence, état de vacations en PDF / Word / Excel, par activité.

## Fonctionnalités
| Écran | Contenu |
|---|---|
| Tableau de bord | verdict ÉQUILIBRÉ/ACCEPTABLE/DÉSÉQUILIBRÉ, KPI, quotas vs affectés vs décret, comparaison par service, tranches de montant, contrôles, 4 graphiques |
| Répartition | matrice agents × activités, montants, écart à la moyenne, statut ; nouveau tirage ; récap Excel/PDF ; clôture de session (historique) |
| Activités | ajouter / modifier / supprimer / désactiver ; jours, taux, quota, total décret, date de début, ordre |
| Agents | CRUD, filtres, import/export Excel, DIRECTION ou EXTERNE, disponibilité, historique |
| Exceptions | matrice F (forcer) / X (exclure), enregistrement immédiat |
| Documents | Décision (visas, pagination 30/34 lignes, « arrêtée la présente page… », nombre en lettres, Art. 2-3, ligne budgétaire), Présence (paysage, 1 colonne par jour, dates), État de vacations (total en lettres) |
| Visas | texte modifiable, activable, extensible |
| Paramètres | session, n° décision, signataire, ligne budgétaire, tolérance, seuils, pas des tranches, services, mode auto, historique |

## Algorithme (identique au classeur Excel)
Charge initiale = historique (option) + montants des activités où l'agent est forcé. Activités traitées dans l'ordre ;
pour chacune : les forcés (F) sont toujours retenus ; il reste `quota − forcés direction` places attribuées aux agents
DIRECTION disponibles, non exclus (X), **les moins chargés d'abord** (départage : ordre de tirage). Les EXTERNES s'ajoutent hors quota.

## Aller plus loin
- Authentification : `composer require laravel/breeze --dev && php artisan breeze:install blade`, puis remplacer `middleware([])` par `['auth']` dans `routes/web.php`.
- Les nouvelles activités / agents / paramètres se gèrent directement dans l'interface (aucun code à modifier).
