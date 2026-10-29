# VACATION — mise à jour 3 (cumulative)

## Installation (à la racine du projet, là où se trouve `artisan`)
```bash
unzip -o Vacation_maj_3.zip -d .
php artisan migrate          # ajoute la colonne « group_type » (et les tables de la mise à jour quarantaine si besoin)
php artisan optimize:clear
```
Sauvegardez `database/database.sqlite` avant.

## Nouveau : sous-groupes pour Dispatching et Correction
| Activité | Sous-groupes |
|---|---|
| Quarantaine | Organisation générale, Superviseur, Coordonateur, Sous pli |
| Dispatching | Agent de dispatching, Agent de sécurité |
| Correction | Correcteur, Agent de secrétariat |

1. **Activités > Modifier** : le champ « Sous-groupes de l'activité » est en mode Automatique (reconnaît « quarantaine », « dispatching » dans le nom ou le code, et un nom qui COMMENCE par « correction »).
   Pour une activité dont le nom ne correspond pas (ex. « Supervision de la correction »), choisissez le type à la main. « Aucun sous-groupe » désactive la fonction.
   Les effectifs prévus par sous-groupe sont facultatifs (0 = pas de limite).
2. **Choisir les agents** : cochez les agents puis choisissez le rôle de chacun dans la liste « Sous-groupe ».
3. **Documents** : comme pour la quarantaine — noms classés par sous-groupe avec arrêtage de chacun, présence / état / décompte indépendants par sous-groupe,
   titre « Activité – Sous-groupe ».
