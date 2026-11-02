# MATCH 64/40

Site de consultation des matchs de basket **seniors** des Pyrénées-Atlantiques (64) et
des Landes (40), de la départementale à la nationale. Les matchs sont importés
automatiquement depuis l'API de la FFBB ; chaque club peut compléter les informations
pratiques de ses matchs à domicile (prix d'entrée, ouverture des portes, buvette,
parking, contact).

Projet de 2e année de **BTS SIO option SLAM** — PHP 8 procédural, architecture MVC
maison, MySQL, sans framework.

## Les trois espaces

| Profil | Ce qu'il peut faire |
| --- | --- |
| Visiteur | Matchs du week-end, tous les matchs à venir, clubs du 64/40 à l'extérieur, recherche par club / niveau / mot-clé, page détaillée d'un match (infos pratiques, classement et calendrier de la poule) |
| Club | Demander un compte, se connecter, compléter les infos pratiques de ses matchs à domicile |
| Administrateur | Valider les comptes clubs, lancer la synchronisation FFBB, ajouter un match à la main |

## Installation (WAMP)

1. Copier le dossier dans `C:\wamp64\www\`.
2. Activer l'extension `php_curl` (icône WAMP → PHP → Extensions PHP).
3. Dans phpMyAdmin, exécuter le script `sql/bdd_complete.sql` : il crée la base
   `projetsitemvcmatchs` et ses trois tables, avec des comptes de test.
4. Ouvrir <http://localhost/projetSiteMvc/>, se connecter en administrateur puis cliquer
   sur « Synchroniser la FFBB ».

Les identifiants de connexion à la base sont en haut de `src/modeles/database.php`
(par défaut `root` sans mot de passe, comme sur WAMP).

## Structure

```
index.php              point d'entrée unique (routage par ?action=)
src/controleurs/       controleurPrincipal.php (routeur) + un contrôleur par page
src/modeles/           database.php, Club.php, Match.php, matchDetail.php,
                       authentification.php, ffbb.php
src/vues/              entete.php, pied.php, outils.php + une vue par page
public/                css/, js/, img/
api/                   ffbb_sync.php (synchro) et test_api.php (page de diagnostic)
cache/                 classements des poules (1 h) et logos téléchargés
sql/                   bdd_complete.sql
```

## Sécurité

Mots de passe hachés (`password_hash`), requêtes préparées PDO, échappement
systématique avec `htmlspecialchars`, contrôle des rôles dans les contrôleurs, liste
fermée d'actions dans le routeur.
