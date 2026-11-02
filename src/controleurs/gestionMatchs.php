<?php

include_once "getRacine.php";
include_once "$racine/src/modeles/Club.php";
include_once "$racine/src/modeles/Match.php";
include_once "$racine/src/modeles/authentification.php";

// Page réservée à l'admin
if (!estAdmin()) {
    header("Location: index.php?action=login_club");
    exit;
}

// Récupérer les données du formulaire
$equipe_domicile = isset($_POST['equipe_domicile']) ? $_POST['equipe_domicile'] : '';
$equipe_exterieur = isset($_POST['equipe_exterieur']) ? $_POST['equipe_exterieur'] : '';
$date_match = isset($_POST['date_match']) ? $_POST['date_match'] : '';
$heure_match = isset($_POST['heure_match']) ? $_POST['heure_match'] : '';
$lieu = isset($_POST['lieu']) ? $_POST['lieu'] : '';
$id_club = isset($_POST['id_club']) ? $_POST['id_club'] : '';
$departement = isset($_POST['departement']) ? $_POST['departement'] : '64';

// Traiter le formulaire
if (!empty($equipe_domicile) && !empty($equipe_exterieur)) {
    $club = getClubById($id_club);
    createMatch($equipe_domicile, $equipe_exterieur, $date_match, $heure_match, $lieu, '', $id_club, '', $departement, $club['nom']);
    $message = "Match créé !";
}

// Récupérer les clubs pour le formulaire
$clubs = getClubsSansAdmin();

// Inclure la vue
$titre = "Gestion des matchs";
include "$racine/src/vues/gestionMatchs.php";

?>
