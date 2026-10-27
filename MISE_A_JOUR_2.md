# VACATION — mise à jour 2 (cumulative : inclut la mise à jour « Quarantaine »)

## Installation (à la racine du projet, là où se trouve `artisan`)
```bash
unzip -o Vacation_maj_2.zip -d .
php artisan migrate          # sans effet si la mise à jour « quarantaine » est déjà installée
php artisan optimize:clear
```
Sauvegardez `database/database.sqlite` avant.

## Ce qui change
1. **Décision — N°** : un blanc visible est ajouté devant « -2026/MEN » (Laravel supprimait les espaces saisis au début du champ).
2. **Décision — articles** : Paramètres > « Articles de la décision »
   - « Article indemnité de vacations + ligne budgétaire » : OUI / NON. Avec NON, l'ancien article 2 disparaît et les suivants se renumérotent tout seuls.
   - « Articles supplémentaires » : un article par ligne, numérotés automatiquement avant l'article final.
   - « Dernier article » : modifiable (vide = supprimé).
3. **Décision — SESSION** : « SESSION 2026 » est maintenant centré, sous le titre de la décision (PDF, Word, Excel).
4. **Note de service** : l'arrêtage n'est plus jamais seul sur une page ; les 2 derniers noms restent avec l'arrêtage, la date et la signature (PDF et Word).
5. **État de vacations** : le taux IRSA est affiché en haut du tableau ; la colonne devient « Net à payer (Ar) » (IRSA déjà déduit), le total et la somme en lettres sont en net. Le taux IRSA se règle dans Documents / Aperçu comme pour le décompte. Excel : taux modifiable, net recalculé.
6. **État de décompte** : chaque page se termine par son « Total de la page » et son « Total à reporter ». Le nombre de lignes par page est calculé pour tenir sur la feuille (1re page plus courte à cause de l'en-tête ; les noms longs comptent double).
   Réglage : Paramètres > « Marge de sécurité de la page » (25 par défaut) : augmentez-la (40, 60…) si une page déborde encore.
