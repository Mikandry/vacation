# VACATION — mise à jour « Quarantaine : sous-groupes »

## Installation (à la racine du projet, là où se trouve `artisan`)

```bash
unzip -o ~/Downloads/Vacation_maj_quarantaine.zip -d .
php artisan migrate
php artisan optimize:clear
php artisan serve
```
Windows : décompressez le zip dans le dossier du projet en acceptant de remplacer les fichiers, puis lancez `php artisan migrate` et `php artisan optimize:clear`.
Faites une copie de sauvegarde de `database/database.sqlite` avant `migrate`.

## Utilisation
1. **Activités → Modifier** l'activité « Quarantaine » (détectée si le code ou le nom contient « quarantaine »).
   Un bloc apparaît : saisissez l'effectif de *Organisation générale*, *Superviseur*, *Coordonateur*, *Sous pli*.
   Le total ne peut pas dépasser le « Total agents prévu par le décret » (à défaut, le « Quota direction »).
2. **Choisir les agents** (page de l'activité) : cochez les agents, puis choisissez le sous-groupe de chacun.
   Les compteurs comparent le choix aux effectifs prévus ; l'enregistrement est refusé si un effectif ou le total est dépassé.
3. **Documents** :
   - Décision et Note de service : noms classés OG, Superviseur, Coordonateur, Sous pli, avec un arrêtage par sous-groupe, puis l'arrêtage général.
   - Présence, État de vacations, État de décompte : un document indépendant par sous-groupe (PDF, Word : une section chacun ; Excel : une feuille chacun),
     avec sa propre en-tête et sa propre fin de page. Le titre porte le nom de l'activité puis du sous-groupe (ex. « Quarantaine – Superviseur »).
   - Les agents cochés sans sous-groupe apparaissent dans un bloc « Non réparti » : à corriger avant d'imprimer.
4. Les autres activités ne changent pas.

Nouveau jeton disponible dans les modèles de texte (Paramètres) : `{sousgroupe}`.
Les jetons `{activite}`, `{titre}`, `{TITRE}` et `{code}` ajoutent automatiquement « – Nom du sous-groupe » dans les documents par sous-groupe.
