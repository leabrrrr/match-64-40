<?php
include_once "getRacine.php";
include_once "$racine/src/modeles/Club.php";
include_once "$racine/src/modeles/Match.php";
include_once "$racine/src/modeles/matchDetail.php";
include_once "$racine/src/modeles/authentification.php";

// Page réservée aux clubs connectés
if (!estConnecte()) {
    header("Location: index.php?action=login_club");
    exit;
}

$club = getClubById(getIdClubConnecte());
$matchs = getMatchsByClubId(getIdClubConnecte());

// Pour chaque match, on regarde si les infos pratiques sont déjà remplies
for ($i = 0; $i < count($matchs); $i++) {
    $matchs[$i]['infos_remplies'] = getDetailsByMatchId($matchs[$i]['id_match']) ? true : false;
}

$titre = "Espace club";
include "$racine/src/vues/pageClub.php";

?>
