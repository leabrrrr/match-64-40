<?php
session_start();

// Heure française (sinon les horaires des matchs peuvent être décalés)
date_default_timezone_set('Europe/Paris');

include_once "getRacine.php";

include_once "$racine/src/modeles/Club.php";
include_once "$racine/src/modeles/Match.php";
include_once "$racine/src/modeles/authentification.php";
include_once "$racine/src/controleurs/controleurPrincipal.php";

$action = isset($_GET['action']) ? $_GET['action'] : 'defaut';
$fichier = controleurPrincipal($action);

include "$racine/src/controleurs/" . $fichier;

?>
