<?php

include_once "getRacine.php";
include_once "$racine/src/modeles/Club.php";
include_once "$racine/src/modeles/Match.php";
include_once "$racine/src/modeles/matchDetail.php";
include_once "$racine/src/modeles/ffbb.php";

$id_match = isset($_GET['id']) ? $_GET['id'] : '';
$match = getMatchById($id_match);

// Match introuvable → retour à l'accueil
if (!$match) {
    header("Location: index.php");
    exit;
}

$club = getClubById($match['id_club']);
$details = getDetailsByMatchId($id_match); // false si pas encore rempli

// Classement et calendrier de la poule (depuis l'API FFBB, gardés 1 h en cache)
$classement = array();
$matchsEquipe = array();
if ($match['id_poule'] != '') {
    $poule = getPouleFFBB($match['id_poule']);

    if ($poule) {
        $classement = $poule['classement'];

        // Tous les matchs de l'équipe qui reçoit, à domicile et à l'extérieur
        foreach ($poule['rencontres'] as $rencontre) {
            if ($rencontre['domicile'] == $match['equipe_domicile'] || $rencontre['exterieur'] == $match['equipe_domicile']) {
                // Ce match est-il dans notre base ? Si oui, on garde son id pour faire un lien
                $rencontre['id_match'] = null;
                if ($rencontre['ffbb_id'] != '') {
                    $matchEnBase = getMatchByFfbbId($rencontre['ffbb_id']);
                    if ($matchEnBase) {
                        $rencontre['id_match'] = $matchEnBase['id_match'];
                    }
                }
                $matchsEquipe[] = $rencontre;
            }
        }
    }
}

$titre = $match['equipe_domicile'] . " - " . $match['equipe_exterieur'];
include "$racine/src/vues/pageMatch.php";

?>
