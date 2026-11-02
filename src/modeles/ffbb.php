<?php
/*
 * MODELE : synchronisation avec l'API officielle de la FFBB
 * ----------------------------------------------------------
 * Même méthode que le prototype testFFBB (qui fonctionne) :
 *
 *   1. GET  https://api.ffbb.app/items/configuration
 *           → donne le jeton public "key_ms"
 *   2. POST https://meilisearch-prod.ffbb.app/multi-search
 *           avec le jeton, une recherche par ville du 64 et du 40
 *           → donne les rencontres
 *   3. on garde les matchs seniors du 64 et du 40 dans la période voulue
 *   4. on les enregistre dans la table matchs (ajout ou mise à jour)
 */

include_once "database.php";
include_once "Match.php";
include_once "Club.php";

// Heure française : indispensable pour convertir les horaires des matchs
date_default_timezone_set('Europe/Paris');

define('FFBB_API_URL', 'https://api.ffbb.app');
define('FFBB_MEILI_URL', 'https://meilisearch-prod.ffbb.app');
define('FFBB_INDEX', 'ffbbserver_rencontres');
// L'application mobile de la FFBB s'annonce comme ça. Le serveur refuse (403)
// les visiteurs qui ressemblent à un navigateur : on fait comme l'appli.
define('FFBB_USER_AGENT', 'okhttp/4.12.0');
define('FFBB_TIMEOUT', 30);

// Dernière erreur rencontrée (affichée à l'admin si la synchro échoue)
$GLOBALS['ffbb_erreur'] = "";
// Nombre de paquets de recherche qui ont échoué (la synchro continue quand même)
$GLOBALS['ffbb_avertissements'] = 0;


// Le moteur de recherche de la FFBB cherche par mots-clés, pas par département.
// On lui pose donc une question par commune du 64 et du 40.
// Un club dont la commune n'est pas dans cette liste ne sera pas trouvé.
function getVillesRecherchees() {
    return array(
        // Pyrénées-Atlantiques (64) : Béarn
        'Pau', 'Billere', 'Lons', 'Jurancon', 'Gelos', 'Bizanos', 'Lescar', 'Lee',
        'Serres-Castet', 'Morlaas', 'Nay', 'Coarraze', 'Pontacq', 'Soumoulou',
        'Oloron', 'Mourenx', 'Artix', 'Lacq', 'Monein', 'Arthez',
        'Orthez', 'Salies-de-Bearn', 'Sauveterre', 'Navarrenx', 'Mauleon',
        // Pyrénées-Atlantiques (64) : Pays basque
        'Bayonne', 'Anglet', 'Biarritz', 'Boucau', 'Bidart', 'Arcangues',
        'Saint-Jean-de-Luz', 'Ciboure', 'Hendaye', 'Urrugne', 'Ascain', 'Guethary',
        'Ustaritz', 'Cambo', 'Hasparren', 'Espelette', 'Itxassou', 'Larressore',
        'Saint-Pierre-d Irube', 'Briscous', 'Bidache', 'Saint-Palais',
        'Saint-Jean-Pied-de-Port', 'Baigorry',
        // Landes (40)
        'Mont-de-Marsan', 'Saint-Pierre-du-Mont', 'Saint-Sever', 'Grenade-sur-l Adour',
        'Villeneuve-de-Marsan', 'Aire-sur-l Adour', 'Roquefort', 'Labrit', 'Sabres',
        'Dax', 'Saint-Paul-les-Dax', 'Tartas', 'Rion-des-Landes', 'Mugron',
        'Montfort-en-Chalosse', 'Amou', 'Gamarde', 'Pouillon', 'Peyrehorade', 'Hagetmau',
        'Tarnos', 'Soustons', 'Capbreton', 'Hossegor', 'Seignosse', 'Labenne',
        'Tyrosse', 'Saint-Geours', 'Castets', 'Leon', 'Vieux-Boucau',
        'Morcenx', 'Parentis', 'Biscarrosse', 'Mimizan', 'Sanguinet'
    );
}


/* ============================================================
   1. APPEL HTTP AVEC CURL
   ============================================================ */

// Envoie une requête et renvoie le JSON décodé (tableau), ou false si erreur.
// $corps = null → requête GET ; $corps = tableau → requête POST en JSON
function appelerFFBB($url, $entetes = array(), $corps = null) {
    if (!function_exists('curl_init')) {
        $GLOBALS['ffbb_erreur'] = "L'extension cURL n'est pas activée dans PHP (WAMP → PHP → Extensions → php_curl)";
        return false;
    }

    if ($corps !== null) {
        $entetes[] = 'Content-Type: application/json';
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);   // renvoyer la réponse au lieu de l'afficher
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, FFBB_TIMEOUT);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $entetes);
    curl_setopt($ch, CURLOPT_USERAGENT, FFBB_USER_AGENT);

    if ($corps !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($corps));
    }

    $reponse = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $erreurCurl = curl_error($ch);
    curl_close($ch);

    if ($reponse === false) {
        $GLOBALS['ffbb_erreur'] = "Échec réseau : " . $erreurCurl;
        return false;
    }

    $donnees = json_decode($reponse, true);

    if ($code >= 400) {
        $detail = isset($donnees['message']) ? $donnees['message'] : substr($reponse, 0, 200);
        $GLOBALS['ffbb_erreur'] = "Réponse HTTP $code : " . $detail;
        return false;
    }

    if (!is_array($donnees)) {
        $GLOBALS['ffbb_erreur'] = "Réponse illisible (ce n'est pas du JSON)";
        return false;
    }

    return $donnees;
}


/* ============================================================
   2. LE JETON
   ============================================================ */

// La FFBB publie librement deux jetons sur /items/configuration :
//   key_ms : pour le moteur de recherche (les rencontres)
//   key_dh : pour l'API (les poules et leurs classements)
// $cle = 'key_ms' ou 'key_dh'
function getJetonFFBB($cle = 'key_ms') {
    $reponse = appelerFFBB(FFBB_API_URL . '/items/configuration');
    if ($reponse === false) {
        return "";
    }

    $donnees = isset($reponse['data']) ? $reponse['data'] : $reponse;

    if (isset($donnees[$cle]) && $donnees[$cle] != '') {
        return $donnees[$cle];
    }

    $GLOBALS['ffbb_erreur'] = "Jeton $cle introuvable dans la configuration FFBB";
    return "";
}


/* ============================================================
   3. LA RECHERCHE DES RENCONTRES
   ============================================================ */

// Envoie une recherche par mot-clé au moteur (une ville ou un nom de club).
// $filtre = filtre de dates, ou "" pour aucun filtre.
// Renvoie les rencontres déjà converties (voir convertirRencontre),
// rangées par identifiant FFBB (donc sans doublon), ou false si ça ne marche pas du tout.
function rechercherRencontres($jeton, $termes, $filtre, $limite) {
    $rencontres = array();

    // Les rencontres de la FFBB sont très lourdes (arbitres, vidéos...) :
    // on ne demande que les champs dont on a besoin, sinon PHP manque de mémoire.
    $champsUtiles = array('id', 'date', 'date_rencontre', 'horaire', 'date_timestamp', 'date_rencontre_timestamp',
        'nomEquipe1', 'nomEquipe2', 'idOrganismeEquipe1', 'idOrganismeEquipe2',
        'competitionId', 'salle', 'niveau', 'idPoule');

    // Liste des recherches à faire : un mot-clé + à partir de quel résultat (offset).
    // Le moteur renvoie au maximum $limite résultats par recherche : quand une
    // recherche est "pleine", on ajoute à la liste la page suivante (offset + $limite).
    $aFaire = array();
    foreach ($termes as $terme) {
        $aFaire[] = array('terme' => $terme, 'offset' => 0);
    }

    $premierPaquet = true;
    while (count($aFaire) > 0) {
        // On prend les 10 prochaines recherches de la liste
        $paquet = array_splice($aFaire, 0, 10);

        $requetes = array();
        foreach ($paquet as $recherche) {
            $requete = array(
                'indexUid' => FFBB_INDEX,
                'q' => $recherche['terme'],
                'limit' => $limite,
                'offset' => $recherche['offset'],
                'attributesToRetrieve' => $champsUtiles
            );
            if ($filtre != "") {
                $requete['filter'] = array($filtre);
            }
            $requetes[] = $requete;
        }

        // On envoie le paquet ; en cas d'échec, on réessaie une fois
        $corps = array('queries' => $requetes);
        $entetes = array('Authorization: Bearer ' . $jeton);
        $reponse = appelerFFBB(FFBB_MEILI_URL . '/multi-search', $entetes, $corps);
        if ($reponse === false || !isset($reponse['results'])) {
            $reponse = appelerFFBB(FFBB_MEILI_URL . '/multi-search', $entetes, $corps);
        }

        if ($reponse === false || !isset($reponse['results'])) {
            // Si le tout premier paquet échoue, c'est que le filtre (ou le jeton) est refusé
            if ($premierPaquet) {
                return false;
            }
            // Sinon on note le problème et on continue avec les paquets suivants
            $GLOBALS['ffbb_avertissements']++;
            continue;
        }
        $premierPaquet = false;

        // Chaque recherche renvoie sa liste de rencontres ("hits").
        // On les convertit tout de suite en petits tableaux, rangés par identifiant :
        // un match trouvé par plusieurs mots-clés n'est gardé qu'une fois.
        foreach ($reponse['results'] as $i => $resultat) {
            $hits = isset($resultat['hits']) ? $resultat['hits'] : array();

            foreach ($hits as $hit) {
                $r = convertirRencontre($hit);
                if ($r['ffbb_id'] != '') {
                    $rencontres[$r['ffbb_id']] = $r;
                }
            }

            // Recherche pleine → il y a sans doute une page suivante
            // (le moteur ne va pas au-delà de 1000 résultats)
            $offsetSuivant = $paquet[$i]['offset'] + $limite;
            if (count($hits) == $limite && $offsetSuivant < 1000) {
                $aFaire[] = array('terme' => $paquet[$i]['terme'], 'offset' => $offsetSuivant);
            }
        }
        unset($reponse); // on libère la mémoire avant le paquet suivant
    }

    return $rencontres;
}

// Récupère les rencontres entre deux dates (format AAAA-MM-JJ)
function getRencontresFFBB($dateDebut, $dateFin) {
    // Plusieurs dizaines de requêtes à la FFBB : on laisse jusqu'à 5 minutes à PHP
    set_time_limit(300);

    $jeton = getJetonFFBB();
    if ($jeton == "") {
        return false;
    }

    // Filtre sur les dates. Le champ date_timestamp peut être en secondes
    // ou en millisecondes (ce n'est pas documenté) : on accepte les deux.
    $debut = strtotime($dateDebut . ' 00:00:00');
    $fin = strtotime($dateFin . ' 23:59:59');
    $filtre = "(date_timestamp >= $debut AND date_timestamp <= $fin)"
            . " OR (date_timestamp >= " . ($debut * 1000) . " AND date_timestamp <= " . ($fin * 1000) . ")";

    $limite = 100;

    // 1er passage : une recherche par commune du 64 et du 40
    $rencontres = rechercherRencontres($jeton, getVillesRecherchees(), $filtre, $limite);

    // Si le serveur refuse le filtre, on recommence sans filtre
    // (on triera les dates nous-mêmes ensuite)
    if ($rencontres === false) {
        $filtre = "";
        $limite = 300;
        $rencontres = rechercherRencontres($jeton, getVillesRecherchees(), $filtre, $limite);
    }
    if ($rencontres === false) {
        return false;
    }

    // 2e passage : une recherche par club du 64/40 trouvé au 1er passage.
    // Ça permet de trouver leurs matchs à l'extérieur (Bordeaux, Toulouse...),
    // qu'une recherche par commune du 64/40 ne trouve pas toujours.
    $clubs = array();
    foreach ($rencontres as $r) {
        if (estDepartementSuivi($r['dep_club_domicile'])) {
            $clubs[$r['club_domicile']] = true;
        }
        if (estDepartementSuivi($r['dep_club_exterieur'])) {
            $clubs[$r['club_exterieur']] = true;
        }
    }
    unset($clubs['']);

    $autres = rechercherRencontres($jeton, array_keys($clubs), $filtre, $limite);
    if ($autres !== false) {
        foreach ($autres as $id => $r) {
            $rencontres[$id] = $r;
        }
    }

    return $rencontres;
}


/* ============================================================
   4. LECTURE D'UNE RENCONTRE
   ============================================================ */

// Renvoie $tableau[$cle] si ça existe et que ce n'est pas vide, sinon $defaut
function lireChamp($tableau, $cle, $defaut = '') {
    if (is_array($tableau) && isset($tableau[$cle]) && $tableau[$cle] !== '') {
        return $tableau[$cle];
    }
    return $defaut;
}

// La FFBB range parfois plusieurs infos dans un seul champ, séparées par "|" :
// "2000...|Régionale masculine seniors - Division 3|uuid|DIV|Masculin|..."
// Le nom lisible est en 2e position.
function nomCompetition($valeur) {
    if (strpos($valeur, '|') === false) {
        return $valeur;
    }
    $morceaux = explode('|', $valeur);
    return trim($morceaux[1]);
}

// Trouve l'heure du match ("15:00"), ou null si elle n'est pas renseignée
function heureRencontre($hit) {
    // 1. le champ "horaire"
    $horaire = (string) lireChamp($hit, 'horaire');
    if (strpos($horaire, 'T') !== false) {
        $horaire = substr($horaire, strpos($horaire, 'T') + 1);
    }
    if (preg_match('/^\d{1,2}:\d{2}/', $horaire)) {
        return substr($horaire, 0, 5);
    }

    // 2. une date qui contient l'heure : date_rencontre = "2026-09-27T15:00:00"
    foreach (array('date_rencontre', 'date') as $cle) {
        $date = (string) lireChamp($hit, $cle);
        if (preg_match('/[T ](\d{2}:\d{2})/', $date, $trouve) && $trouve[1] != '00:00') {
            return $trouve[1];
        }
    }

    // 3. l'horodatage (en secondes ou millisecondes)
    foreach (array('date_rencontre_timestamp', 'date_timestamp') as $cle) {
        $ts = lireChamp($hit, $cle, null);
        if (is_numeric($ts)) {
            if ($ts > 100000000000) {
                $ts = $ts / 1000; // c'étaient des millisecondes
            }
            $heure = date('H:i', (int) $ts);
            if ($heure != '00:00') {
                return $heure;
            }
        }
    }

    return null;
}

// Un identifiant de logo FFBB est un UUID : "f40a09e2-32dd-488c-9138-283f6b52a498".
// On vérifie ce format car l'identifiant finit dans une URL et un nom de fichier.
function identifiantLogoValide($id) {
    return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id) == 1;
}

// Le logo peut arriver sous deux formes :
//   un objet   : { "id": "uuid", "gradient_color": "#e30066" }
//   une chaîne : "uuid"
// Renvoie l'identifiant, ou null s'il n'y a pas de logo
function lireLogo($objet) {
    $logo = lireChamp($objet, 'logo', null);
    if (is_array($logo)) {
        $logo = lireChamp($logo, 'id', null);
    }
    if (is_string($logo) && identifiantLogoValide($logo)) {
        return $logo;
    }
    return null;
}

// Vrai pour les départements suivis par le site
function estDepartementSuivi($departement) {
    return $departement == '64' || $departement == '40';
}

// Département d'un club d'après son code FFBB :
// "NAQ0064074" → Nouvelle-Aquitaine, département 0064 → "64"
function departementClub($organisme) {
    $code = (string) lireChamp($organisme, 'code');
    if (preg_match('/^[A-Z]{3}00(\d{2})\d{3}$/', $code, $trouve)) {
        return $trouve[1];
    }
    return '';
}

// Vrai si la rencontre concerne le 64/40 : jouée dans le 64/40,
// ou avec un club du 64/40 (même s'il joue ailleurs)
function concerne6440($r) {
    return estDepartementSuivi($r['departement'])
        || estDepartementSuivi($r['dep_club_domicile'])
        || estDepartementSuivi($r['dep_club_exterieur']);
}

// Vrai si la rencontre est une compétition seniors
function estSenior($competition) {
    $categorie = lireChamp($competition, 'categorie', array());
    if (lireChamp($categorie, 'code') == 'SE') {
        return true;
    }
    if (stripos((string) lireChamp($categorie, 'libelle'), 'senior') === 0) {
        return true;
    }
    return false;
}

// Transforme une rencontre de la FFBB en tableau simple pour notre base
function convertirRencontre($hit) {
    $salle = lireChamp($hit, 'salle', array());
    $carto = lireChamp($salle, 'cartographie', array());
    $organisateur = lireChamp($hit, 'organisateur', array());
    $commune = lireChamp($organisateur, 'commune', array());
    // Le club qui reçoit : idOrganismeEquipe1 = { nom: "ASC PAU BC", ... }
    // (attention : "organisateur" est souvent la ligue ou le comité, pas le club)
    $clubDomicile = lireChamp($hit, 'idOrganismeEquipe1', array());
    $clubExterieur = lireChamp($hit, 'idOrganismeEquipe2', array());
    $competition = lireChamp($hit, 'competitionId', array());
    $poule = lireChamp($hit, 'idPoule', array()); // { id: "200000003054340", nom: "Poule G" }

    // Logo de la division : celui de la compétition, sinon celui de son type
    // (le même logo pour toutes les "Régionale masculine seniors", etc.)
    $logoCompetition = lireLogo($competition);
    if ($logoCompetition === null) {
        $logoCompetition = lireLogo(lireChamp($competition, 'typeCompetitionGenerique', array()));
    }

    // Niveau : "Départemental", "Régional" ou "National"
    $niveau = (string) lireChamp($hit, 'niveau');
    if ($niveau == '') {
        $morceaux = explode('|', (string) lireChamp($competition, 'nomExtended'));
        $niveau = isset($morceaux[8]) ? $morceaux[8] : '';
    }

    // La commune de la salle : dans "cartographie" (moteur de recherche)
    // ou dans "commune" (calendrier d'une poule)
    $communeSalle = lireChamp($salle, 'commune', array());

    // Code postal : celui de la salle, sinon celui du club organisateur
    $codePostal = (string) lireChamp($carto, 'codePostal', lireChamp($communeSalle, 'codePostal', lireChamp($commune, 'codePostal')));

    // Lieu = "GYMNASE DU HEDAS, Pau"
    $lieu = lireChamp($salle, 'libelle', 'Salle à préciser');
    $ville = lireChamp($carto, 'ville');
    if ($ville == '' && lireChamp($communeSalle, 'libelle') != '') {
        // "CASTEL-SARRAZIN" → "Castel-Sarrazin"
        $ville = mb_convert_case(mb_strtolower($communeSalle['libelle']), MB_CASE_TITLE);
    }
    if ($ville != '') {
        $lieu = $lieu . ", " . $ville;
    }

    return array(
        'ffbb_id' => (string) lireChamp($hit, 'id'),
        'date' => substr((string) lireChamp($hit, 'date', lireChamp($hit, 'date_rencontre')), 0, 10),
        'heure' => heureRencontre($hit),
        'domicile' => mb_substr(lireChamp($hit, 'nomEquipe1', 'Équipe à domicile'), 0, 100),
        'exterieur' => mb_substr(lireChamp($hit, 'nomEquipe2', 'Équipe visiteuse'), 0, 100),
        'lieu' => mb_substr($lieu, 0, 100),
        'competition' => mb_substr(nomCompetition(lireChamp($competition, 'nomExtended', lireChamp($competition, 'nom'))), 0, 100),
        'niveau' => mb_substr($niveau, 0, 20),
        'id_poule' => (string) lireChamp($poule, 'id'),
        'nom_poule' => mb_substr((string) lireChamp($poule, 'nom'), 0, 50),
        'senior' => estSenior($competition),
        'logo_domicile' => lireLogo($clubDomicile),
        'logo_exterieur' => lireLogo($clubExterieur),
        'logo_competition' => $logoCompetition,
        'departement' => substr($codePostal, 0, 2),
        'club_domicile' => mb_substr((string) lireChamp($clubDomicile, 'nom'), 0, 100),
        'club_exterieur' => mb_substr((string) lireChamp($clubExterieur, 'nom'), 0, 100),
        'dep_club_domicile' => departementClub($clubDomicile),
        'dep_club_exterieur' => departementClub($clubExterieur)
    );
}

// Cherche le club de notre base qui reçoit le match.
// On compare avec le nom du club à domicile donné par la FFBB, puis avec le nom
// de l'équipe à domicile sans son numéro ("LONS BASKET - 2" → "LONS BASKET").
// Renvoie l'id du club, ou null si le club n'est pas inscrit sur le site.
function trouverClub($rencontre) {
    if ($rencontre['club_domicile'] != '') {
        $club = getClubByNom($rencontre['club_domicile']);
        if ($club) {
            return $club['id_club'];
        }
    }

    $nomEquipe = preg_replace('/ - \d+$/', '', $rencontre['domicile']);
    $club = getClubByNom($nomEquipe);
    if ($club) {
        return $club['id_club'];
    }

    return null;
}


/* ============================================================
   5. LES LOGOS
   ============================================================ */

// Télécharge un logo sur le serveur de la FFBB.
// Renvoie array('contenu' => ..., 'type' => 'image/png') ou false.
function telechargerLogoFFBB($id) {
    if (!function_exists('curl_init') || !identifiantLogoValide($id)) {
        return false;
    }

    $ch = curl_init(FFBB_API_URL . '/assets/' . $id);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, FFBB_TIMEOUT);
    curl_setopt($ch, CURLOPT_USERAGENT, FFBB_USER_AGENT);
    $contenu = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $type = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    // On n'accepte que des images (sinon c'est une page d'erreur)
    if ($contenu === false || $code >= 400 || $contenu === '' || strpos($type, 'image/') !== 0) {
        return false;
    }

    return array('contenu' => $contenu, 'type' => $type);
}


/* ============================================================
   6. LA POULE : CLASSEMENT ET CALENDRIER
   ============================================================ */

// Fonctions de tri utilisées par usort() : elles renvoient un nombre
// négatif si $a doit passer avant $b, positif sinon.
function comparerPosition($a, $b) {
    return $a['position'] - $b['position'];
}

function comparerDate($a, $b) {
    return strcmp($a['date'], $b['date']);
}

// Calcule un classement à partir des rencontres d'une poule.
// Règle FFBB : victoire = 2 points, défaite = 1 point.
function calculerClassement($rencontres) {
    // Une ligne par équipe, rangée par nom
    $equipes = array();
    foreach ($rencontres as $r) {
        foreach (array($r['domicile'], $r['exterieur']) as $nom) {
            if ($nom != '' && !isset($equipes[$nom])) {
                $equipes[$nom] = array('position' => 0, 'equipe' => $nom, 'logo' => null,
                    'joues' => 0, 'gagnes' => 0, 'perdus' => 0, 'points' => 0, 'difference' => 0);
            }
        }
    }

    // On ajoute les résultats des matchs déjà joués
    foreach ($rencontres as $r) {
        if ($r['score_domicile'] === null || $r['score_exterieur'] === null) {
            continue;
        }
        $ecart = $r['score_domicile'] - $r['score_exterieur'];
        $gagnant = $ecart > 0 ? $r['domicile'] : $r['exterieur'];
        $perdant = $ecart > 0 ? $r['exterieur'] : $r['domicile'];

        $equipes[$gagnant]['joues']++;
        $equipes[$gagnant]['gagnes']++;
        $equipes[$gagnant]['points'] += 2;
        $equipes[$gagnant]['difference'] += abs($ecart);

        $equipes[$perdant]['joues']++;
        $equipes[$perdant]['perdus']++;
        $equipes[$perdant]['points'] += 1;
        $equipes[$perdant]['difference'] -= abs($ecart);
    }

    // Tri : points, puis différence, puis nom
    $classement = array_values($equipes);
    usort($classement, 'comparerPoints');
    for ($i = 0; $i < count($classement); $i++) {
        $classement[$i]['position'] = $i + 1;
    }
    return $classement;
}

function comparerPoints($a, $b) {
    if ($a['points'] != $b['points']) {
        return $b['points'] - $a['points'];
    }
    if ($a['difference'] != $b['difference']) {
        return $b['difference'] - $a['difference'];
    }
    return strcmp($a['equipe'], $b['equipe']);
}

// Récupère le classement et tous les matchs d'une poule.
// Renvoie array('classement' => [...], 'rencontres' => [...]) ou false.
// Le résultat est gardé 1 heure dans cache/poules/ pour ne pas appeler
// la FFBB à chaque fois qu'on ouvre la page d'un match.
function getPouleFFBB($idPoule) {
    // L'identifiant d'une poule ne contient que des chiffres (sécurité : nom de fichier)
    if (!ctype_digit((string) $idPoule)) {
        return false;
    }

    $dossier = __DIR__ . '/../../cache/poules';
    // "-v2" : si on change le contenu gardé en cache, on change ce nom
    // pour que les anciens fichiers ne soient plus utilisés
    $fichier = "$dossier/$idPoule-v2.json";

    // Déjà en cache depuis moins d'une heure ?
    if (is_file($fichier) && time() - filemtime($fichier) < 3600) {
        return json_decode(file_get_contents($fichier), true);
    }

    $jeton = getJetonFFBB('key_dh');
    if ($jeton == "") {
        return false;
    }

    // On ne demande que les champs utiles ("fields[]"), et jusqu'à 1000
    // classements / rencontres ("deep[...][_limit]")
    $champs = array(
        'classements.position', 'classements.points', 'classements.matchJoues',
        'classements.gagnes', 'classements.perdus', 'classements.difference',
        'classements.idEngagement.nom', 'classements.idEngagement.logo.id',
        'classements.organisme.nom', 'classements.organisme.logo.id',
        'rencontres.id', 'rencontres.numeroJournee', 'rencontres.date_rencontre',
        'rencontres.nomEquipe1', 'rencontres.nomEquipe2',
        'rencontres.resultatEquipe1', 'rencontres.resultatEquipe2', 'rencontres.joue'
    );
    $url = FFBB_API_URL . '/items/ffbbserver_poules/' . $idPoule
         . '?deep[classements][_limit]=1000&deep[rencontres][_limit]=1000';
    foreach ($champs as $champ) {
        $url .= '&fields[]=' . urlencode($champ);
    }

    $reponse = appelerFFBB($url, array('Authorization: Bearer ' . $jeton));
    if ($reponse === false || !isset($reponse['data'])) {
        return false;
    }
    $donnees = $reponse['data'];

    // Classement, simplifié et trié par position
    $classement = array();
    foreach (lireChamp($donnees, 'classements', array()) as $ligne) {
        $engagement = lireChamp($ligne, 'idEngagement', array());
        $organisme = lireChamp($ligne, 'organisme', array());
        $logo = lireLogo($engagement);
        if ($logo === null) {
            $logo = lireLogo($organisme);
        }
        $classement[] = array(
            'position' => (int) lireChamp($ligne, 'position', 0),
            'equipe' => (string) lireChamp($engagement, 'nom', lireChamp($organisme, 'nom')),
            'logo' => $logo,
            'joues' => (int) lireChamp($ligne, 'matchJoues', 0),
            'gagnes' => (int) lireChamp($ligne, 'gagnes', 0),
            'perdus' => (int) lireChamp($ligne, 'perdus', 0),
            'points' => (int) lireChamp($ligne, 'points', 0),
            'difference' => (int) lireChamp($ligne, 'difference', 0)
        );
    }
    usort($classement, 'comparerPosition');

    // Rencontres, simplifiées et triées par date
    $rencontres = array();
    foreach (lireChamp($donnees, 'rencontres', array()) as $ligne) {
        $date = (string) lireChamp($ligne, 'date_rencontre');
        $rencontres[] = array(
            'ffbb_id' => (string) lireChamp($ligne, 'id'),
            'journee' => lireChamp($ligne, 'numeroJournee'),
            'date' => substr($date, 0, 10),
            'heure' => (strlen($date) >= 16 && substr($date, 11, 5) != '00:00') ? substr($date, 11, 5) : null,
            'domicile' => (string) lireChamp($ligne, 'nomEquipe1'),
            'exterieur' => (string) lireChamp($ligne, 'nomEquipe2'),
            // Les scores arrivent en texte ("61") : on les transforme en nombres
            'score_domicile' => is_numeric(lireChamp($ligne, 'resultatEquipe1', null)) ? (int) $ligne['resultatEquipe1'] : null,
            'score_exterieur' => is_numeric(lireChamp($ligne, 'resultatEquipe2', null)) ? (int) $ligne['resultatEquipe2'] : null
        );
    }
    usort($rencontres, 'comparerDate');

    // En début de saison, la FFBB ne publie le classement qu'après le 1er match joué :
    // dans ce cas on le calcule nous-mêmes à partir des rencontres
    if (count($classement) == 0) {
        $classement = calculerClassement($rencontres);
    }

    $poule = array('classement' => $classement, 'rencontres' => $rencontres);

    // On garde le résultat en cache
    if (!is_dir($dossier)) {
        mkdir($dossier, 0775, true);
    }
    file_put_contents($fichier, json_encode($poule));

    return $poule;
}


// Le moteur de recherche de la FFBB ne contient que les matchs du week-end à venir.
// Pour les semaines suivantes, on lit le calendrier complet des poules.
// $poules = array(idPoule => match "modèle" de cette poule, qui donne la compétition,
//                 le niveau, le logo de la division...)
// Renvoie les rencontres converties (comme rechercherRencontres).
function getCalendriersPoulesFFBB($poules) {
    $jeton = getJetonFFBB('key_dh');
    if ($jeton == "" || count($poules) == 0) {
        return array();
    }

    // Les mêmes informations que dans le moteur de recherche
    $champs = array('id', 'nom',
        'rencontres.id', 'rencontres.date_rencontre', 'rencontres.nomEquipe1', 'rencontres.nomEquipe2',
        'rencontres.idOrganismeEquipe1.nom', 'rencontres.idOrganismeEquipe1.code', 'rencontres.idOrganismeEquipe1.logo.id',
        'rencontres.idOrganismeEquipe2.nom', 'rencontres.idOrganismeEquipe2.code', 'rencontres.idOrganismeEquipe2.logo.id',
        'rencontres.salle.libelle', 'rencontres.salle.commune.libelle', 'rencontres.salle.commune.codePostal');

    $rencontres = array();

    // 10 poules par requête : filter = {"id": {"_in": [id1, id2, ...]}}
    foreach (array_chunk(array_keys($poules), 10) as $paquet) {
        $url = FFBB_API_URL . '/items/ffbbserver_poules'
             . '?filter=' . urlencode(json_encode(array('id' => array('_in' => $paquet))))
             . '&deep[rencontres][_limit]=1000&limit=10';
        foreach ($champs as $champ) {
            $url .= '&fields[]=' . urlencode($champ);
        }

        $reponse = appelerFFBB($url, array('Authorization: Bearer ' . $jeton));
        if ($reponse === false) {
            $reponse = appelerFFBB($url, array('Authorization: Bearer ' . $jeton)); // 2e essai
        }
        if ($reponse === false || !isset($reponse['data'])) {
            $GLOBALS['ffbb_avertissements']++;
            continue;
        }

        foreach ($reponse['data'] as $poule) {
            $modele = $poules[$poule['id']];

            foreach (lireChamp($poule, 'rencontres', array()) as $hit) {
                $r = convertirRencontre($hit);
                // Ce qui n'est pas dans le calendrier vient du match modèle de la poule
                $r['competition'] = $modele['competition'];
                $r['niveau'] = $modele['niveau'];
                $r['logo_competition'] = $modele['logo_competition'];
                $r['id_poule'] = (string) $poule['id'];
                $r['nom_poule'] = $modele['nom_poule'];
                $r['senior'] = true;
                if ($r['ffbb_id'] != '') {
                    $rencontres[$r['ffbb_id']] = $r;
                }
            }
        }
    }

    return $rencontres;
}


/* ============================================================
   7. LA SYNCHRO (appelée par l'admin)
   ============================================================ */

// Importe les matchs SENIORS du 64/40 des $nbJours prochains jours (30 par défaut) :
// ceux joués dans le 64/40, et ceux des clubs du 64/40 joués ailleurs
function syncFFBB($nbJours = 30) {
    $dateDebut = date('Y-m-d');
    $dateFin = date('Y-m-d', strtotime("+$nbJours days"));

    // 1. Le moteur de recherche : les matchs du week-end à venir
    $rencontres = getRencontresFFBB($dateDebut, $dateFin);

    if ($rencontres === false) {
        return "Erreur : " . $GLOBALS['ffbb_erreur'];
    }

    // 2. Le calendrier des poules seniors du 64/40 : les semaines suivantes
    $poules = array();
    foreach ($rencontres as $r) {
        if ($r['senior'] && concerne6440($r) && $r['id_poule'] != '') {
            $poules[$r['id_poule']] = $r; // ce match sert de "modèle" pour la poule
        }
    }
    foreach (getCalendriersPoulesFFBB($poules) as $id => $r) {
        if (!isset($rencontres[$id])) {
            $rencontres[$id] = $r;
        }
    }

    $ajoutes = 0;
    $modifies = 0;

    foreach ($rencontres as $r) {
        // On ignore : les matchs qui ne concernent pas le 64/40, hors période, pas seniors
        if (!concerne6440($r)) {
            continue;
        }
        if ($r['date'] < $dateDebut || $r['date'] > $dateFin) {
            continue;
        }
        if (!$r['senior']) {
            continue;
        }

        $id_club = trouverClub($r);

        // Déjà en base → mise à jour (l'horaire a pu changer), sinon ajout
        $matchExistant = getMatchByFfbbId($r['ffbb_id']);
        if ($matchExistant) {
            updateMatchFFBB($matchExistant['id_match'], $r, $id_club);
            $modifies++;
        } else {
            createMatchFFBB($r, $id_club);
            $ajoutes++;
        }
    }

    $message = "Sync réussie ! " . count($rencontres) . " rencontres reçues de la FFBB, "
         . "$ajoutes matchs seniors ajoutés et $modifies mis à jour (du "
         . date('d/m', strtotime($dateDebut)) . " au " . date('d/m', strtotime($dateFin)) . ")";

    if ($GLOBALS['ffbb_avertissements'] > 0) {
        $message .= " (attention : " . $GLOBALS['ffbb_avertissements'] . " recherche(s) n'ont pas répondu, relance la synchro)";
    }
    return $message;
}

?>
