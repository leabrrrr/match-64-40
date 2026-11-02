<?php

include_once "getRacine.php";
include_once "$racine/src/modeles/ffbb.php";
include_once "$racine/src/modeles/authentification.php";

// Page réservée à l'admin
if (!estAdmin()) {
    header("Location: index.php?action=login_club");
    exit;
}

// Appeler la fonction
$message = syncFFBB();

// Inclure la vue
$titre = "Sync FFBB";
include "$racine/src/vues/syncFFBB.php";

?>
