<?php

function controleurPrincipal($action) {
    $lesActions = array();
    // Public
    $lesActions["defaut"] = "accueil.php";
    $lesActions["accueil"] = "accueil.php";
    $lesActions["detail_match"] = "detailMatch.php";
    $lesActions["logo"] = "logo.php";
    // Clubs
    $lesActions["login_club"] = "loginClub.php";
    $lesActions["inscription_club"] = "inscriptionClub.php";
    $lesActions["dashboard_club"] = "dashboardClub.php";
    $lesActions["modifier_match"] = "modifierMatch.php";
    $lesActions["deconnexion"] = "deconnexion.php";
    // Admin
    $lesActions["admin"] = "admin.php";
    $lesActions["gestion_matchs"] = "gestionMatchs.php";
    $lesActions["sync_ffbb"] = "syncFFBB.php";

    if (array_key_exists($action, $lesActions)) {
        return $lesActions[$action];
    }
    else {
        return $lesActions["defaut"];
    }
}

?>
