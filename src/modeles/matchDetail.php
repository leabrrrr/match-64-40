<?php
include_once "database.php";

// Récupère les infos pratiques d'un match (prix, portes, buvette...)
// Retourne false si le club ne les a pas encore remplies
function getDetailsByMatchId($id_match){
    $pdo = connexionPDO();
    $query = "SELECT * FROM match_details WHERE id_match = ?";
    $params = [$id_match];

    return fetchOne($pdo, $query, $params);
}

function createDetails($id_match, $prix_entree, $horaire_portes, $description, $gps, $telephone, $mail, $buvette, $parking){
    $pdo = connexionPDO();
    $query = "INSERT INTO match_details (prix_entree, horaire_ouvertures_portes, description_organisation, coordonnees_gps, contact_telephone, contact_mail, buvette, parking, id_match)
    VALUES (?,?,?,?,?,?,?,?,?)";
    $params = [$prix_entree, $horaire_portes, $description, $gps, $telephone, $mail, $buvette, $parking, $id_match];

    return executeQuery($pdo, $query, $params);
}

function updateDetails($id_match, $prix_entree, $horaire_portes, $description, $gps, $telephone, $mail, $buvette, $parking){
    $pdo = connexionPDO();
    $query = "UPDATE match_details SET prix_entree = ?, horaire_ouvertures_portes = ?, description_organisation = ?, coordonnees_gps = ?, contact_telephone = ?, contact_mail = ?, buvette = ?, parking = ?
    WHERE id_match = ?";
    $params = [$prix_entree, $horaire_portes, $description, $gps, $telephone, $mail, $buvette, $parking, $id_match];

    return executeQuery($pdo, $query, $params);
}

// Si les infos existent déjà on les modifie, sinon on les crée
function enregistrerDetails($id_match, $prix_entree, $horaire_portes, $description, $gps, $telephone, $mail, $buvette, $parking){
    $details = getDetailsByMatchId($id_match);

    if ($details) {
        updateDetails($id_match, $prix_entree, $horaire_portes, $description, $gps, $telephone, $mail, $buvette, $parking);
    } else {
        createDetails($id_match, $prix_entree, $horaire_portes, $description, $gps, $telephone, $mail, $buvette, $parking);
    }
}

?>
