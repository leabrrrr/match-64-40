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

$message = "";

// Validation d'un compte club : index.php?action=admin&valider=3
if (isset($_GET['valider'])) {
    validerClub($_GET['valider']);
    $message = "Compte club validé !";
}

// Création d'un compte club par l'admin (validé directement)
if (isset($_POST['nom'])) {
    if (getClubByEmail($_POST['email'])) {
        $message = "Un compte existe déjà avec cet email";
    } else {
        createClub($_POST['nom'], $_POST['email'], $_POST['mdp'], $_POST['description'], $_POST['adresse'], $_POST['telephone'], '');
        $nouveauClub = getClubByEmail($_POST['email']);
        validerClub($nouveauClub['id_club']);
        $message = "Club créé !";
    }
}

$clubs = getClubsSansAdmin();
$matchs = getMatchsAVenir();

$titre = "Admin";
include "$racine/src/vues/admin.php";

?>
