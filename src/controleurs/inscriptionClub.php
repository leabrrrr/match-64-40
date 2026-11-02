<?php
include_once "getRacine.php";
include_once "$racine/src/modeles/Club.php";

$erreur = "";
$message = "";

if (isset($_POST['nom'])) {
    $nom = $_POST['nom'];
    $email = $_POST['email'];
    $mdp = $_POST['mdp'];
    $description = $_POST['description'];
    $adresse = $_POST['adresse'];
    $telephone = $_POST['telephone'];

    if (getClubByEmail($email)) {
        $erreur = "Un compte existe déjà avec cet email";
    } else if (strlen($mdp) < 6) {
        $erreur = "Le mot de passe doit faire au moins 6 caractères";
    } else {
        // Le compte est créé avec valide = 0 : l'admin doit le valider
        createClub($nom, $email, $mdp, $description, $adresse, $telephone, '');
        $message = "Demande envoyée ! Vous pourrez vous connecter dès que l'administrateur aura validé votre compte.";
    }
}

$titre = "Inscription Club";
include "$racine/src/vues/inscriptionClub.php";

?>
