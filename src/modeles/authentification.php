<?php
include_once "Club.php";

// Vérifie l'email et le mot de passe, puis enregistre le club en session
// Retourne un message d'erreur, ou "" si la connexion a réussi
function login($email, $mdp) {
    $club = getClubByEmail($email);

    if (!$club || !password_verify($mdp, $club['mdp'])) {
        return "Email ou mot de passe incorrect";
    }
    if ($club['valide'] == 0) {
        return "Votre compte n'a pas encore été validé par l'administrateur";
    }

    $_SESSION['id_club'] = $club['id_club'];
    $_SESSION['nom'] = $club['nom'];
    $_SESSION['role'] = $club['role'];
    return "";
}

function logout() {
    unset($_SESSION['id_club']);
    unset($_SESSION['nom']);
    unset($_SESSION['role']);
}

function estConnecte() {
    return isset($_SESSION['id_club']);
}

function estAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
}

function getIdClubConnecte() {
    return $_SESSION['id_club'];
}

?>
