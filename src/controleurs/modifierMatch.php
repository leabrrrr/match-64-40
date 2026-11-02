<?php
include_once "getRacine.php";
include_once "$racine/src/modeles/Match.php";
include_once "$racine/src/modeles/matchDetail.php";
include_once "$racine/src/modeles/authentification.php";

// Page réservée aux clubs connectés
if (!estConnecte()) {
    header("Location: index.php?action=login_club");
    exit;
}

$id_match = isset($_GET['id']) ? $_GET['id'] : '';
$match = getMatchById($id_match);

// Un club ne peut modifier que SES matchs (l'admin peut tout modifier)
if (!$match || ($match['id_club'] != getIdClubConnecte() && !estAdmin())) {
    header("Location: index.php?action=dashboard_club");
    exit;
}

$message = "";

// Le formulaire a été envoyé
if (isset($_POST['description_organisation'])) {
    // Champs vides → NULL dans la base (sinon erreur pour DECIMAL et TIME)
    $prix = $_POST['prix_entree'] != '' ? $_POST['prix_entree'] : null;
    $portes = $_POST['horaire_ouvertures_portes'] != '' ? $_POST['horaire_ouvertures_portes'] : null;
    // Une case à cocher n'est envoyée que si elle est cochée
    $buvette = isset($_POST['buvette']) ? 1 : 0;
    $parking = isset($_POST['parking']) ? 1 : 0;

    enregistrerDetails($id_match, $prix, $portes, $_POST['description_organisation'], $_POST['coordonnees_gps'],
        $_POST['contact_telephone'], $_POST['contact_mail'], $buvette, $parking);
    $message = "Informations enregistrées !";
}

// Infos déjà enregistrées (pour pré-remplir le formulaire)
$details = getDetailsByMatchId($id_match);

$titre = "Modifier les infos du match";
include "$racine/src/vues/modifierMatch.php";

?>
