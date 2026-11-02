<?php
include_once "database.php";

function getAllClubs() {
    $pdo = connexionPDO();  // ← Connexion
    $query = "SELECT * FROM clubs";
    $params = [];
    
    return fetchAll($pdo, $query, $params);  // ← On retourne le résultat
}

function getClubById($id_club){
    $pdo = connexionPDO();
    $query = "SELECT * from clubs WHERE id_club= ?";
    $params = [$id_club];

    return fetchOne($pdo, $query, $params);
 }

function getClubByEmail($email){
    $pdo = connexionPDO();
    $query = "SELECT * from clubs WHERE email = ?";
    $params = [$email];

    return fetchOne($pdo, $query, $params);
}

function createClub($nom, $email, $mdp, $description, $adresse, $telephone, $logo){
    $pdo = connexionPDO();
    $mdpHash = password_hash($mdp, PASSWORD_BCRYPT);  // ← Hash le mdp

    $query = "INSERT INTO clubs (nom, email, mdp, description, adresse, telephone, logo) 
    VALUES (?,?,?,?,?,?,?)";
    $params = [$nom, $email, $mdpHash, $description, $adresse, $telephone, $logo];

    return executeQuery($pdo, $query, $params);
}

function updateClub($id_club, $nom, $email, $mdp, $description, $adresse, $telephone, $logo){
    $pdo = connexionPDO();
    $mdpHash = password_hash($mdp, PASSWORD_BCRYPT);  // ← Hash le mdp


    $query = "UPDATE clubs SET nom = ?, email = ?, mdp = ?, description = ?, adresse = ?, telephone = ?, logo = ?
    WHERE id_club = ?";
    $params = [$nom, $email, $mdpHash, $description, $adresse, $telephone, $logo, $id_club];

    return executeQuery($pdo, $query, $params);
}

function getClubByNom($nom){
    $pdo = connexionPDO();
    $query = "SELECT * from clubs WHERE nom = ?";
    $params = [$nom];

    return fetchOne($pdo, $query, $params);
}

// Tous les clubs sauf le compte administrateur
function getClubsSansAdmin(){
    $pdo = connexionPDO();
    $query = "SELECT * from clubs WHERE role = 'club' ORDER BY valide, nom";
    $params = [];

    return fetchAll($pdo, $query, $params);
}

// L'admin valide le compte d'un club (valide passe à 1)
function validerClub($id_club){
    $pdo = connexionPDO();
    $query = "UPDATE clubs SET valide = 1 WHERE id_club = ?";
    $params = [$id_club];

    return executeQuery($pdo, $query, $params);
}
