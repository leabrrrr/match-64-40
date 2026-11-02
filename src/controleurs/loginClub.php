<?php
include_once "getRacine.php";
include_once "$racine/src/modeles/authentification.php";

$erreur = "";

// Le formulaire a été envoyé
if (isset($_POST['email']) && isset($_POST['mdp'])) {
    $erreur = login($_POST['email'], $_POST['mdp']);
}

// Déjà connecté → on redirige vers la bonne page
if (estConnecte()) {
    if (estAdmin()) {
        header("Location: index.php?action=admin");
    } else {
        header("Location: index.php?action=dashboard_club");
    }
    exit;
}

$titre = "Connexion Club";
include "$racine/src/vues/loginClub.php";

?>
